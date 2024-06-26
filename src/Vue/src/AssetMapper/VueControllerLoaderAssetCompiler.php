<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Vue\AssetMapper;

use Symfony\Component\AssetMapper\AssetDependency;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * Compiles the components.js file to dynamically import the Vue controller components.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class VueControllerLoaderAssetCompiler implements AssetCompilerInterface
{
    public function __construct(
        private string $controllerPath,
        private array $nameGlobs,
    ) {
    }

    public function supports(MappedAsset $asset): bool
    {
        return $asset->sourcePath === realpath(__DIR__.'/../../assets/dist/components.js');
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        $importLines = [];
        $componentParts = [];
        foreach ($this->findControllerAssets($assetMapper) as $name => $mappedAsset) {
            // @legacy: backwards compatibility with Symfony 6.3
            if (class_exists(AssetDependency::class)) {
                $controllerPublicPath = $mappedAsset->publicPathWithoutDigest;
                $loaderPublicPath = $asset->publicPathWithoutDigest;
                $relativeImportPath = Path::makeRelative($controllerPublicPath, \dirname($loaderPublicPath));
            } else {
                $relativeImportPath = Path::makeRelative($mappedAsset->sourcePath, \dirname($asset->sourcePath));
            }

            $controllerNameForVariable = \sprintf('component_%s', \count($componentParts));

            $importLines[] = \sprintf(
                "import %s from '%s';",
                $controllerNameForVariable,
                $relativeImportPath
            );
            $componentParts[] = \sprintf('"%s": %s', $name, $controllerNameForVariable);
        }

        $importCode = implode("\n", $importLines);
        $componentsJson = \sprintf('{%s}', implode(', ', $componentParts));

        return <<<EOF
        $importCode
        export const components = $componentsJson;
        EOF;
    }

    /**
     * @return MappedAsset[]
     */
    private function findControllerAssets(AssetMapperInterface $assetMapper): array
    {
        if (!class_exists(Finder::class)) {
            throw new \LogicException('The "symfony/finder" package is required to use ux-Vue with AssetMapper. Try running "composer require symfony/finder".');
        }

        if (!file_exists($this->controllerPath)) {
            return [];
        }

        $finder = new Finder();
        $finder->in($this->controllerPath)
            ->files()
            ->name($this->nameGlobs)
        ;
        $assets = [];
        foreach ($finder as $file) {
            $asset = $assetMapper->getAssetFromSourcePath($file->getRealPath());

            if (null === $asset) {
                throw new \LogicException(\sprintf('Could not find an asset mapper path for the Vue controller file "%s".', $file->getRealPath()));
            }

            $name = $file->getRelativePathname();
            $name = substr($name, 0, -\strlen($file->getExtension()) - 1);

            $assets[$name] = $asset;
        }

        return $assets;
    }
}
