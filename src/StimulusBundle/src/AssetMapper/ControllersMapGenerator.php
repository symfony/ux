<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\AssetMapper;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapGenerator;
use Symfony\Component\Finder\Finder;
use Symfony\UX\StimulusBundle\Ux\UxPackageMetadata;
use Symfony\UX\StimulusBundle\Ux\UxPackageReader;

/**
 * Finds all Stimulus controllers in the project & controllers.json.
 *
 * @internal
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class ControllersMapGenerator
{
    private const FILENAME_REGEX = '/^.*[-_](controller\.[jt]s)$/';

    public function __construct(
        private AssetMapperInterface $assetMapper,
        private UxPackageReader $uxPackageReader,
        private array $controllerPaths,
        private string $controllersJsonPath,
        private ?AutoImportLocator $autoImportLocator = null,
        private array $applicationControllerPaths = [],
        private ?string $baseControllersJsonPath = null,
    ) {
    }

    /**
     * @return array<string, MappedControllerAsset>
     */
    public function getControllersMap(): array
    {
        return array_merge(
            $this->loadUxControllers(),
            $this->loadCustomControllers($this->controllerPaths),
            $this->loadCustomControllers($this->applicationControllerPaths),
        );
    }

    /**
     * @return list<string>
     */
    public function getControllersJsonPaths(): array
    {
        return null === $this->baseControllersJsonPath ? [$this->controllersJsonPath] : [$this->baseControllersJsonPath, $this->controllersJsonPath];
    }

    public function getControllerPaths(): array
    {
        return [...$this->controllerPaths, ...$this->applicationControllerPaths];
    }

    /**
     * @return array<string, MappedControllerAsset>
     */
    private function loadCustomControllers(array $paths): array
    {
        if ([] === $paths) {
            return [];
        }

        $finder = new Finder();
        $finder->in($paths)
            ->files()
            ->name(self::FILENAME_REGEX)
            // the filesystem iteration order is not stable, sort to keep the
            // generated controllers loader (and so its digest) deterministic
            ->sortByName();

        $controllersMap = [];
        foreach ($finder as $file) {
            // Skip .ts controller if .js version is available
            if ('ts' === $file->getExtension() && file_exists(substr($file->getRealPath(), 0, -2).'js')) {
                continue;
            }

            $name = $file->getRelativePathname();
            // use regex to extract 'controller'-postfix including extension
            preg_match(self::FILENAME_REGEX, $name, $matches);
            $name = str_replace(['_'.$matches[1], '-'.$matches[1]], '', $name);
            $name = str_replace(['_', '/', '\\'], ['-', '--', '--'], $name);

            $asset = $this->assetMapper->getAssetFromSourcePath($file->getRealPath());
            if (!$asset) {
                throw new \RuntimeException(\sprintf('Could not find an asset mapper path that points to the "%s" controller.', $name));
            }

            $content = file_get_contents($asset->sourcePath);
            $isLazy = preg_match('/\/\*!?\s*stimulusFetch:\s*\'lazy\'\s*\*\//i', $content);

            $controllersMap[$name] = new MappedControllerAsset($asset, $isLazy);
        }

        return $controllersMap;
    }

    /**
     * @return array<string, MappedControllerAsset>
     */
    private function loadUxControllers(): array
    {
        $controllersMap = [];
        foreach ($this->readControllersJson() as $packageName => $packageControllers) {
            foreach ($packageControllers as $controllerName => [$localControllerConfig, $controllersJsonPath]) {
                try {
                    $packageMetadata = $this->uxPackageReader->readPackageMetadata($packageName);
                } catch (\RuntimeException $e) {
                    throw new \RuntimeException(rtrim($e->getMessage(), '.').\sprintf(' (read from "%s").', $controllersJsonPath), 0, $e);
                }

                $controllerReference = $packageName.'/'.$controllerName;
                $packageControllerConfig = $packageMetadata->symfonyConfig['controllers'][$controllerName] ?? null;

                if (null === $packageControllerConfig) {
                    throw new \RuntimeException(\sprintf('Controller "%s" does not exist in the "%s" package (read from "%s").', $controllerReference, $packageMetadata->packageName, $controllersJsonPath));
                }

                if (!$localControllerConfig['enabled']) {
                    continue;
                }

                $controllerMainPath = $packageMetadata->packageDirectory.'/'.$packageControllerConfig['main'];
                $fetchMode = $localControllerConfig['fetch'] ?? 'eager';
                $lazy = 'lazy' === $fetchMode;

                $controllerNormalizedName = substr($controllerReference, 1);
                $controllerNormalizedName = str_replace(['_', '/'], ['-', '--'], $controllerNormalizedName);

                if (isset($packageControllerConfig['name'])) {
                    $controllerNormalizedName = str_replace('/', '--', $packageControllerConfig['name']);
                }

                if (isset($localControllerConfig['name'])) {
                    $controllerNormalizedName = str_replace('/', '--', $localControllerConfig['name']);
                }

                $asset = $this->assetMapper->getAssetFromSourcePath($controllerMainPath);
                if (!$asset) {
                    throw new \RuntimeException(\sprintf('Could not find an asset mapper path that points to the "%s" controller in package "%s", defined in controllers.json.', $controllerName, $packageMetadata->packageName));
                }

                $autoImports = $this->collectAutoImports($localControllerConfig['autoimport'] ?? [], $packageMetadata);

                $controllersMap[$controllerNormalizedName] = new MappedControllerAsset($asset, $lazy, $autoImports);
            }
        }

        return $controllersMap;
    }

    /**
     * Reads the controllers.json file, merged over the base file when there is one.
     *
     * @return array<string, array<string, array{array<string, mixed>, string}>> the config of each controller and the file it is read from
     */
    private function readControllersJson(): array
    {
        $controllers = [];
        foreach ($this->getControllersJsonPaths() as $controllersJsonPath) {
            if (!is_file($controllersJsonPath)) {
                continue;
            }

            $jsonData = json_decode(file_get_contents($controllersJsonPath), true, 512, \JSON_THROW_ON_ERROR);

            foreach ($jsonData['controllers'] ?? [] as $packageName => $packageControllers) {
                foreach ($packageControllers as $controllerName => $controllerConfig) {
                    $baseControllerConfig = $controllers[$packageName][$controllerName][0] ?? [];
                    $mergedControllerConfig = array_replace($baseControllerConfig, $controllerConfig);
                    if (isset($baseControllerConfig['autoimport'], $controllerConfig['autoimport'])) {
                        $mergedControllerConfig['autoimport'] = array_replace($baseControllerConfig['autoimport'], $controllerConfig['autoimport']);
                    }

                    $controllers[$packageName][$controllerName] = [$mergedControllerConfig, $controllersJsonPath];
                }
            }
        }

        return $controllers;
    }

    /**
     * @return MappedControllerAutoImport[]
     */
    private function collectAutoImports(array $autoImports, UxPackageMetadata $currentPackageMetadata): array
    {
        // @legacy: Backwards compatibility with Symfony 6.3
        if (!class_exists(ImportMapGenerator::class)) {
            return [];
        }
        if (null === $this->autoImportLocator) {
            throw new \InvalidArgumentException(\sprintf('The "autoImportLocator" argument to "%s" is required when using AssetMapper 6.4', self::class));
        }

        $autoImportItems = [];
        foreach ($autoImports as $path => $enabled) {
            if (!$enabled) {
                continue;
            }

            $autoImportItems[] = $this->autoImportLocator->locateAutoImport($path, $currentPackageMetadata);
        }

        return $autoImportItems;
    }
}
