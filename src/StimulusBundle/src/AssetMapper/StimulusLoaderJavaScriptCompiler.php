<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\AssetMapper;

use Symfony\Component\AssetMapper\AssetDependency;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerPathResolverTrait;
use Symfony\Component\AssetMapper\MappedAsset;

/**
 * Compiles the loader.js file to dynamically import the controllers.
 *
 * @experimental
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class StimulusLoaderJavaScriptCompiler implements AssetCompilerInterface
{
    use AssetCompilerPathResolverTrait;

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
        $loaderPublicPath = $asset->publicPathWithoutDigest;

        // add file dependencies so the cache rebuilds
        $asset->addFileDependency($this->controllersMapGenerator->getControllersJsonPath());
        foreach ($this->controllersMapGenerator->getControllerPaths() as $controllerDir) {
            $asset->addFileDependency($controllerDir);
        }

        foreach ($this->controllersMapGenerator->getControllersMap() as $name => $mappedControllerAsset) {
            $controllerPublicPath = $mappedControllerAsset->asset->publicPathWithoutDigest;
            $relativeImportPath = $this->createRelativePath($loaderPublicPath, $controllerPublicPath);

            /*
             * The AssetDependency will already be added by AssetMapper itself when
             * it processes this file. However, due to the "stimulusFetch: 'lazy'"
             * that may appear inside the controllers, this file is dependent on
             * the "contents" of each controller. So, we add the dependency here
             * and mark it as a "content" dependency so that this file's contents
             * will be recalculated when the contents of any controller changes.
             */
            $asset->addDependency(new AssetDependency(
                $mappedControllerAsset->asset,
                $mappedControllerAsset->isLazy,
                true,
            ));

            if ($mappedControllerAsset->isLazy) {
                $lazyControllers[] = sprintf('%s: () => import(%s)', json_encode($name), json_encode($relativeImportPath, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES));
                continue;
            }

            $controllerNameForVariable = sprintf('controller_%s', \count($eagerControllerParts));

            $importLines[] = sprintf(
                "import %s from '%s';",
                $controllerNameForVariable,
                $relativeImportPath
            );
            $eagerControllerParts[] = sprintf('"%s": %s', $name, $controllerNameForVariable);
        }

        $importCode = implode("\n", $importLines);
        $eagerControllersJson = sprintf('{%s}', implode(', ', $eagerControllerParts));
        $lazyControllersExpression = sprintf('{%s}', implode(', ', $lazyControllers));

        $isDebugString = $this->isDebug ? 'true' : 'false';

        return <<<EOF
        $importCode
        export const eagerControllers = $eagerControllersJson;
        export const lazyControllers = $lazyControllersExpression;
        export const isApplicationDebug = $isDebugString;
        EOF;
    }
}
