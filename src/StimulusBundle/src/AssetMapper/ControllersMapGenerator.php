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
    ) {
    }

    /**
     * @return array<string, MappedControllerAsset>
     */
    public function getControllersMap(): array
    {
        return array_merge(
            $this->loadUxControllers(),
            $this->loadCustomControllers(),
        );
    }

    public function getControllersJsonPath(): string
    {
        return $this->controllersJsonPath;
    }

    public function getControllerPaths(): array
    {
        return $this->controllerPaths;
    }

    /**
     * @return array<string, MappedControllerAsset>
     */
    private function loadCustomControllers(): array
    {
        $finder = new Finder();
        $finder->in($this->controllerPaths)
            ->files()
            ->name(self::FILENAME_REGEX);

        $controllersMap = [];
        foreach ($finder as $file) {
            $name = $file->getRelativePathname();
            // use regex to extract 'controller'-postfix including extension
            preg_match(self::FILENAME_REGEX, $name, $matches);
            $name = str_replace(['_'.$matches[1], '-'.$matches[1]], '', $name);
            $name = str_replace(['_', '/', '\\'], ['-', '--', '--'], $name);

            $asset = $this->assetMapper->getAssetFromSourcePath($file->getRealPath());
            $content = $asset->content ?: file_get_contents($asset->sourcePath);
            $isLazy = preg_match('/\/\*\s*stimulusFetch:\s*\'lazy\'\s*\*\//i', $content);

            $controllersMap[$name] = new MappedControllerAsset($asset, $isLazy);
        }

        return $controllersMap;
    }

    /**
     * @return array<string, MappedControllerAsset>
     */
    private function loadUxControllers(): array
    {
        if (!is_file($this->controllersJsonPath)) {
            return [];
        }

        $jsonData = json_decode(file_get_contents($this->controllersJsonPath), true, 512, \JSON_THROW_ON_ERROR);

        $controllersList = $jsonData['controllers'] ?? [];

        $controllersMap = [];
        foreach ($controllersList as $packageName => $packageControllers) {
            foreach ($packageControllers as $controllerName => $localControllerConfig) {
                $packageMetadata = $this->uxPackageReader->readPackageMetadata($packageName);

                $controllerReference = $packageName.'/'.$controllerName;
                $packageControllerConfig = $packageMetadata->symfonyConfig['controllers'][$controllerName] ?? null;

                if (null === $packageControllerConfig) {
                    throw new \RuntimeException(sprintf('Controller "%s" does not exist in the "%s" package.', $controllerReference, $packageMetadata->packageName));
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
                    throw new \RuntimeException(sprintf('Could not find an asset mapper path that points to the "%s" controller in package "%s", defined in controllers.json.', $controllerName, $packageMetadata->packageName));
                }

                $autoImports = $this->collectAutoImports($localControllerConfig['autoimport'] ?? [], $packageMetadata);

                $controllersMap[$controllerNormalizedName] = new MappedControllerAsset($asset, $lazy, $autoImports);
            }
        }

        return $controllersMap;
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
            throw new \InvalidArgumentException(sprintf('The "autoImportLocator" argument to "%s" is required when using AssetMapper 6.4', self::class));
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
