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
    private const CORE_ASSET = '@symfony/stimulus-bundle/core.js';

    /**
     * @var array<string, string>|null
     */
    private ?array $applicationLoaderRealpaths = null;

    /**
     * @param array<string, array{loader: string, generator: ControllersMapGenerator}> $applications
     */
    public function __construct(
        private ControllersMapGenerator $controllersMapGenerator,
        private bool $isDebug,
        private array $applications = [],
    ) {
    }

    public function supports(MappedAsset $asset): bool
    {
        return $asset->sourcePath === realpath(__DIR__.'/../../assets/dist/controllers.js')
            || null !== $this->findApplicationGenerator($asset->sourcePath);
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        $applicationGenerator = $this->findApplicationGenerator($asset->sourcePath);
        if (null === $applicationGenerator) {
            return $this->compileControllers($this->controllersMapGenerator, $asset);
        }

        $coreAsset = $assetMapper->getAsset(self::CORE_ASSET);
        if (null === $coreAsset) {
            throw new \LogicException(\sprintf('The "%s" asset cannot be found. Make sure the StimulusBundle assets are available to AssetMapper.', self::CORE_ASSET));
        }

        $coreImportPath = json_encode(self::makeRelativeImport($coreAsset->sourcePath, \dirname($asset->sourcePath)), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        return \sprintf("import { startApplication } from %s;\n", $coreImportPath)
            .$this->compileControllers($applicationGenerator, $asset)
            ."\nexport const startStimulusApp = () => startApplication(eagerControllers, lazyControllers, isApplicationDebug);";
    }

    private function findApplicationGenerator(string $sourcePath): ?ControllersMapGenerator
    {
        if (null === $this->applicationLoaderRealpaths) {
            $this->applicationLoaderRealpaths = [];
            foreach ($this->applications as $name => $application) {
                if (false !== $realpath = realpath($application['loader'])) {
                    $this->applicationLoaderRealpaths[$realpath] = $name;
                }
            }
        }

        $name = $this->applicationLoaderRealpaths[$sourcePath] ?? null;

        return null === $name ? null : $this->applications[$name]['generator'];
    }

    private function compileControllers(ControllersMapGenerator $controllersMapGenerator, MappedAsset $asset): string
    {
        $importLines = [];
        $eagerControllerParts = [];
        $lazyControllers = [];

        // add file dependencies so the cache rebuilds
        foreach ($controllersMapGenerator->getControllersJsonPaths() as $controllersJsonPath) {
            $asset->addFileDependency($controllersJsonPath);
        }
        foreach ($controllersMapGenerator->getControllerPaths() as $controllerDir) {
            $asset->addFileDependency($controllerDir);
        }

        foreach ($controllersMapGenerator->getControllersMap() as $name => $mappedControllerAsset) {
            // @legacy: backwards compatibility with Symfony 6.3
            if (class_exists(AssetDependency::class)) {
                $loaderPublicPath = $asset->publicPathWithoutDigest;
                $controllerPublicPath = $mappedControllerAsset->asset->publicPathWithoutDigest;
                $relativeImportPath = self::makeRelativeImport($controllerPublicPath, \dirname($loaderPublicPath));
            } else {
                $relativeImportPath = self::makeRelativeImport($mappedControllerAsset->asset->sourcePath, \dirname($asset->sourcePath));
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
                    $autoImportPaths[] = json_encode(self::makeRelativeImport($autoImport->path, \dirname($asset->sourcePath)), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
                }
            }

            if ($mappedControllerAsset->isLazy) {
                if (!$mappedControllerAsset->autoImports) {
                    $lazyControllers[] = \sprintf('%s: () => import(%s)', json_encode($name), $relativeImportPath);
                } else {
                    // import $relativeImportPath and also the auto-imports
                    // and use a Promise.all() to wait for all of them
                    $lazyControllers[] = \sprintf('%s: () => Promise.all([import(%s), %s]).then((ret) => ret[0])', json_encode($name), $relativeImportPath, implode(', ', array_map(static fn ($path) => "import($path)", $autoImportPaths)));
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

    private static function makeRelativeImport(string $path, string $basePath): string
    {
        $relativePath = Path::makeRelative($path, $basePath);

        return str_starts_with($relativePath, '../') ? $relativePath : './'.$relativePath;
    }
}
