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
 * Compiles the Stimulus modules: the global loader.js and the module of each application.
 *
 * Both get the same shape: the runtime, the controllers inlined, and the same exports.
 *
 * @internal
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class StimulusLoaderJavaScriptCompiler implements AssetCompilerInterface
{
    private const RUNTIME_ASSET = '@symfony/stimulus-bundle/runtime.js';
    private const STUB = "export { startStimulusApp } from '@symfony/stimulus-bundle';";
    private const STUB_REGEX = '/^export\s*\{\s*startStimulusApp\s*\}\s*from\s*([\'"])@symfony\/stimulus-bundle\1\s*;?$/';

    /**
     * @var array<string, string>|null
     */
    private ?array $applicationModuleRealpaths = null;

    /**
     * @param array<string, array{loader: string, generator: ControllersMapGenerator, watched_paths?: list<string>}> $applications
     */
    public function __construct(
        private ControllersMapGenerator $controllersMapGenerator,
        private bool $isDebug,
        private array $applications = [],
    ) {
    }

    public function supports(MappedAsset $asset): bool
    {
        return $asset->sourcePath === realpath(__DIR__.'/../../assets/dist/loader.js')
            || $asset->sourcePath === realpath(__DIR__.'/../../assets/dist/controllers.js')
            || null !== $this->findApplicationName($asset->sourcePath)
            || self::containsOnlyTheStub($asset);
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        // Nothing imports controllers.js any more; it stays compiled for code that imports it directly.
        if ($asset->sourcePath === realpath(__DIR__.'/../../assets/dist/controllers.js')) {
            return $this->compileControllers($this->controllersMapGenerator, $asset);
        }

        $generator = $this->controllersMapGenerator;
        if (null !== $name = $this->findApplicationName($asset->sourcePath)) {
            $trimmedContent = trim($content);
            if ('' !== $trimmedContent && !preg_match(self::STUB_REGEX, $trimmedContent)) {
                throw new \LogicException(\sprintf('The "%s" module of the "%s" Stimulus application is generated: its content is replaced when the asset is compiled. Leave it empty or containing only "%s", and move your code to the entrypoint that imports it.', $asset->sourcePath, $name, self::STUB));
            }

            $generator = $this->applications[$name]['generator'];
            foreach ($this->applications[$name]['watched_paths'] ?? [] as $watchedPath) {
                $asset->addFileDependency($watchedPath);
            }
        } elseif (preg_match(self::STUB_REGEX, trim($content))) {
            // Served as is, the stub would silently start the global application.
            throw new \LogicException(\sprintf('The "%s" module contains the Stimulus application stub, but no Stimulus application uses it. Declare it under "stimulus.applications" (as "assets/<name>/stimulus.js" or with the "loader" option), or import "@symfony/stimulus-bundle" directly.', $asset->sourcePath));
        }

        $runtimeAsset = $assetMapper->getAsset(self::RUNTIME_ASSET);
        if (null === $runtimeAsset) {
            throw new \LogicException(\sprintf('The "%s" asset cannot be found. Make sure the StimulusBundle assets are available to AssetMapper.', self::RUNTIME_ASSET));
        }

        $runtimeImportPath = json_encode(self::makeRelativeImport($runtimeAsset->sourcePath, \dirname($asset->sourcePath)), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        return \sprintf("import { loadControllers, startApplication } from %s;\n", $runtimeImportPath)
            .$this->compileControllers($generator, $asset)
            ."\nexport const startStimulusApp = () => startApplication(eagerControllers, lazyControllers, isApplicationDebug);"
            ."\nexport { loadControllers };";
    }

    private function findApplicationName(string $sourcePath): ?string
    {
        if (null === $this->applicationModuleRealpaths) {
            $this->applicationModuleRealpaths = [];
            foreach ($this->applications as $name => $application) {
                if (false !== $realpath = realpath($application['loader'])) {
                    $this->applicationModuleRealpaths[$realpath] = $name;
                }
            }
        }

        return $this->applicationModuleRealpaths[$sourcePath] ?? null;
    }

    private static function containsOnlyTheStub(MappedAsset $asset): bool
    {
        if ($asset->isVendor || !str_ends_with($asset->sourcePath, '.js') || !is_file($asset->sourcePath) || filesize($asset->sourcePath) > 128) {
            return false;
        }

        return 1 === preg_match(self::STUB_REGEX, trim((string) file_get_contents($asset->sourcePath)));
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
