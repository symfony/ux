<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Preview;

use Symfony\Component\AssetMapper\ImportMap\ImportMapType;
use Symfony\Component\AssetMapper\ImportMap\RemotePackageStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Component\StimulusController;
use Symfony\UX\Toolkit\Dependency\DependencyInterface;
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Kit\Kit;

/**
 * Writes the Tailwind entry and the JavaScript entrypoint that preview a kit in an AssetMapper application.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PreviewAssetsGenerator
{
    public function __construct(
        private readonly RemotePackageStorage $remotePackageStorage,
        private readonly Filesystem $filesystem,
    ) {
    }

    public static function entrypointName(string $kitName): string
    {
        return 'ux-toolkit-'.$kitName;
    }

    /**
     * @return array{script: string, stylesheet: ?string} the stylesheet is only generated for kits shipping a kit.css
     */
    public static function getOutputFiles(string $kitName, string $kitDir, string $outputDir): array
    {
        $entrypoint = Path::join($outputDir, self::entrypointName($kitName));

        return [
            'script' => $entrypoint.'.js',
            'stylesheet' => is_file(Path::join($kitDir, 'kit.css')) ? $entrypoint.'.css' : null,
        ];
    }

    /**
     * @return list<string> the paths of the generated files
     */
    public function generate(string $kitName, Kit $kit, string $outputDir): array
    {
        ['script' => $script, 'stylesheet' => $stylesheet] = self::getOutputFiles($kitName, $kit->absolutePath, $outputDir);

        $this->dumpFile($script, $this->generateScript($kit, $outputDir, $stylesheet));
        if (null === $stylesheet) {
            return [$script];
        }

        $this->dumpFile($stylesheet, $this->generateStylesheet($kit));

        return [$script, $stylesheet];
    }

    private function generateStylesheet(Kit $kit): string
    {
        $lines = ['@import "tailwindcss";'];
        foreach ($this->getStylesheetPackages($kit) as $package) {
            $stylesheet = $this->remotePackageStorage->getDownloadPath($package, ImportMapType::CSS);
            $lines[] = \sprintf('@import %s;', self::cssString($stylesheet));
        }
        $lines[] = \sprintf('@import %s;', self::cssString(Path::join($kit->absolutePath, 'kit.css')));

        foreach (self::getImportmapPackages($kit->manifest->dependencies) as $package) {
            if (!str_ends_with($package, '.css')) {
                $packageDir = \dirname($this->remotePackageStorage->getDownloadPath($package, ImportMapType::JS));
                $lines[] = \sprintf('@source %s;', self::cssString($packageDir));
            }
        }
        $lines[] = \sprintf('@source %s;', self::cssString($kit->absolutePath));

        return implode("\n", $lines)."\n";
    }

    private function generateScript(Kit $kit, string $outputDir, ?string $stylesheet): string
    {
        $lines = [];
        if (null !== $stylesheet) {
            $lines[] = \sprintf('import %s;', self::jsString('./'.basename($stylesheet)));
        } else {
            foreach ($this->getStylesheetPackages($kit) as $package) {
                $lines[] = \sprintf('import %s;', self::jsString($package));
            }
        }

        $kitScript = Path::join($kit->absolutePath, 'kit.js');
        if (is_file($kitScript)) {
            $lines[] = \sprintf('import %s;', self::jsString(self::relativeImport($kitScript, $outputDir)));
        }

        $lines[] = 'import { Application } from "@hotwired/stimulus";';

        $registrations = [];
        foreach ($this->findControllers($kit) as $i => $controller) {
            $lines[] = \sprintf('import controller%d from %s;', $i, self::jsString(self::relativeImport($controller, $outputDir)));
            $registrations[] = \sprintf('app.register(%s, controller%d);', self::jsString(StimulusController::identifier($controller)), $i);
        }

        return implode("\n", [...$lines, '', 'const app = Application.start();', ...$registrations])."\n";
    }

    /**
     * @return list<string> the absolute paths of the controllers the recipes of the kit install
     */
    private function findControllers(Kit $kit): array
    {
        $controllers = [];
        foreach ($kit->getRecipes() as $recipe) {
            foreach ($recipe->getFiles() as $file) {
                $source = $file->sourceRelativePathName;
                if (StimulusController::isFilename($source) && str_starts_with($source, 'assets/controllers/')) {
                    $controllers[] = Path::join($recipe->absolutePath, $source);
                }
            }
        }
        sort($controllers);

        return $controllers;
    }

    /**
     * @return list<string> the stylesheets the kit and its recipes declare as importmap packages
     */
    private function getStylesheetPackages(Kit $kit): array
    {
        $packages = self::getImportmapPackages($kit->manifest->dependencies);
        foreach ($kit->getRecipes() as $recipe) {
            array_push($packages, ...self::getImportmapPackages($recipe->manifest->dependencies));
        }

        $stylesheets = array_filter($packages, static fn (string $package): bool => str_ends_with($package, '.css'));

        return array_values(array_unique($stylesheets));
    }

    /**
     * @param list<DependencyInterface> $dependencies
     *
     * @return list<string>
     */
    private static function getImportmapPackages(array $dependencies): array
    {
        $packages = [];
        foreach ($dependencies as $dependency) {
            if ($dependency instanceof ImportmapPackageDependency) {
                $packages[] = $dependency->package;
            }
        }

        return $packages;
    }

    private function dumpFile(string $path, string $content): void
    {
        if (!is_file($path) || file_get_contents($path) !== $content) {
            $this->filesystem->dumpFile($path, $content);
        }
    }

    private static function relativeImport(string $path, string $fromDir): string
    {
        $relativePath = Path::makeRelative($path, $fromDir);

        return str_starts_with($relativePath, '../') ? $relativePath : './'.$relativePath;
    }

    private static function cssString(string $path): string
    {
        return '"'.addcslashes(Path::canonicalize($path), '"\\').'"';
    }

    private static function jsString(string $value): string
    {
        return json_encode($value, \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);
    }
}
