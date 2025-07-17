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

use Symfony\Component\AssetMapper\AssetDependency;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Path;

/**
 * Compiles the loader.js file to dynamically import the controllers.
 *
 * @internal
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class StimulusLoaderJavaScriptCompiler implements AssetCompilerInterface
{
    public function __construct(
        private ControllersMapGenerator $controllersMapGenerator,
        private bool $isDebug,
    ) {
    }

    public function supports(MappedAsset $asset): bool
    {
        return $asset->sourcePath === realpath(__DIR__.'/../../assets/dist/controllers.js');
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        $importLines = [];
        $eagerControllerParts = [];
        $lazyControllers = [];

        // add file dependencies so the cache rebuilds
        $asset->addFileDependency($this->controllersMapGenerator->getControllersJsonPath());
        foreach ($this->controllersMapGenerator->getControllerPaths() as $controllerDir) {
            $asset->addFileDependency($controllerDir);
        }

        foreach ($this->controllersMapGenerator->getControllersMap() as $name => $mappedControllerAsset) {
            // @legacy: backwards compatibility with Symfony 6.3
            if (class_exists(AssetDependency::class)) {
                $loaderPublicPath = $asset->publicPathWithoutDigest;
                $controllerPublicPath = $mappedControllerAsset->asset->publicPathWithoutDigest;
                $relativeImportPath = Path::makeRelative($controllerPublicPath, \dirname($loaderPublicPath));
            } else {
                $relativeImportPath = Path::makeRelative($mappedControllerAsset->asset->sourcePath, \dirname($asset->sourcePath));
            }

            $relativeImportPath = json_encode($relativeImportPath, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

            /*
             * The AssetDependency will already be added by AssetMapper itself when
             * it processes this file. However, due to the "stimulusFetch: 'lazy'"
             * that may appear inside the controllers, this file is dependent on
             * the "contents" of each controller. So, we add the dependency here
             * and mark it as a "content" dependency so that this file's contents
             * will be recalculated when the contents of any controller changes.
             */
            if (class_exists(AssetDependency::class)) {
                // @legacy: Backwards compatibility with Symfony 6.3
                $asset->addDependency(new AssetDependency(
                    $mappedControllerAsset->asset,
                    $mappedControllerAsset->isLazy,
                    true,
                ));
            } else {
                $asset->addDependency($mappedControllerAsset->asset);
            }

            $autoImportPaths = [];
            foreach ($mappedControllerAsset->autoImports as $autoImport) {
                if ($autoImport->isBareImport) {
                    $autoImportPaths[] = json_encode($autoImport->path, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
                } else {
                    $autoImportPaths[] = json_encode(Path::makeRelative($autoImport->path, \dirname($asset->sourcePath)), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
                }
            }

            if ($mappedControllerAsset->isLazy) {
                if (!$mappedControllerAsset->autoImports) {
                    $lazyControllers[] = \sprintf('%s: () => import(%s)', json_encode($name), $relativeImportPath);
                } else {
                    // import $relativeImportPath and also the auto-imports
                    // and use a Promise.all() to wait for all of them
                    $lazyControllers[] = \sprintf('%s: () => Promise.all([import(%s), %s]).then((ret) => ret[0])', json_encode($name), $relativeImportPath, implode(', ', array_map(fn ($path) => "import($path)", $autoImportPaths)));
                }

                continue;
            }

            $controllerNameForVariable = \sprintf('controller_%s', \count($eagerControllerParts));

            $importLines[] = \sprintf(
                'import %s from %s;',
                $controllerNameForVariable,
                $relativeImportPath
            );
            foreach ($autoImportPaths as $autoImportRelativePath) {
                $importLines[] = \sprintf(
                    'import %s;',
                    $autoImportRelativePath
                );
            }
            $eagerControllerParts[] = \sprintf('"%s": %s', $name, $controllerNameForVariable);
        }

        $importCode = implode("\n", $importLines);
        $eagerControllersJson = \sprintf('{%s}', implode(', ', $eagerControllerParts));
        $lazyControllersExpression = \sprintf('{%s}', implode(', ', $lazyControllers));

        $isDebugString = $this->isDebug ? 'true' : 'false';

        return <<<EOF
        $importCode
        export const eagerControllers = $eagerControllersJson;
        export const lazyControllers = $lazyControllersExpression;
        export const isApplicationDebug = $isDebugString;
        EOF;
    }
}
