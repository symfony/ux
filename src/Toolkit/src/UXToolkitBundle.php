<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Toolkit\DependencyInjection\PreviewPass;
use Symfony\UX\Toolkit\Registry\LocalRegistry;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
class UXToolkitBundle extends AbstractBundle
{
    private const PREVIEW_DIR = 'var/ux_toolkit/preview';

    protected string $extensionAlias = 'ux_toolkit';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('preview')
                    ->info('Wires the preview assets of kits into AssetMapper, Tailwind and the importmap.')
                    ->canBeEnabled()
                    ->children()
                        ->arrayNode('kits')
                            ->info('The kits to preview: names of kits shipped with the Toolkit, or directories of external kits, absolute or relative to the project directory. Defaults to every kit shipped with the Toolkit.')
                            ->performNoDeepMerging()
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $previewConfig = $this->getPreviewConfig($builder);
        if (!$previewConfig['enabled']) {
            return;
        }

        $kitDirs = $this->resolvePreviewKitDirs($previewConfig['kits'], $builder->getParameter('kernel.project_dir'));
        $builder->setParameter('.ux_toolkit.preview.kit_dirs', $kitDirs);
        $builder->setParameter('.ux_toolkit.preview.output_dir', '%kernel.project_dir%/'.self::PREVIEW_DIR);

        $paths = [self::PREVIEW_DIR => '@symfony/ux-toolkit/preview'];
        $excludedPatterns = [];
        foreach ($kitDirs as $kitName => $kitDir) {
            $paths[$kitDir] = '@symfony/ux-toolkit/kits/'.$kitName;
            foreach (self::getNonScriptFilePatterns($kitDir) as $nonScriptFiles) {
                $excludedPatterns[] = $kitDir.'{,/**}/'.$nonScriptFiles;
            }
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => $paths,
                'excluded_patterns' => $excludedPatterns,
            ],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        if ($builder->hasParameter('.ux_toolkit.preview.kit_dirs')) {
            $container->import('../config/preview.php');
        }
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PreviewPass());
    }

    /**
     * @return array{enabled: bool, kits: list<string>}
     */
    private function getPreviewConfig(ContainerBuilder $builder): array
    {
        $configuration = $this->getContainerExtension()->getConfiguration([], $builder);

        $configs = $builder->resolveEnvPlaceholders($builder->getParameterBag()->resolveValue($builder->getExtensionConfig('ux_toolkit')), true);

        return new Processor()->processConfiguration($configuration, $configs)['preview'];
    }

    /**
     * Previews only need the scripts of a kit, so every other file type found in it is kept out of AssetMapper.
     *
     * @return list<string> glob patterns matching the file names to exclude
     */
    private static function getNonScriptFilePatterns(string $kitDir): array
    {
        $patterns = [];
        foreach (new Finder()->in($kitDir)->files() as $file) {
            $extension = $file->getExtension();
            if ('js' !== $extension) {
                $patterns['' === $extension ? $file->getFilename() : '*.'.$extension] = true;
            }
        }

        return array_keys($patterns);
    }

    /**
     * @param list<string> $kits names of kits shipped with the Toolkit, or directories of external kits
     *
     * @return array<string, string> kit name => absolute path of the kit directory
     */
    private function resolvePreviewKitDirs(array $kits, string $projectDir): array
    {
        $localKitNames = LocalRegistry::getAvailableKitsName();

        $kitDirs = [];
        foreach ([] === $kits ? $localKitNames : $kits as $kit) {
            if (\in_array($kit, $localKitNames, true)) {
                $kitDir = Path::join(LocalRegistry::getKitsDir(), $kit);
            } elseif (is_file(Path::join($kitDir = Path::makeAbsolute($kit, $projectDir), 'manifest.json'))) {
                $kitDir = Path::canonicalize(realpath($kitDir));
            } else {
                throw new \InvalidArgumentException(\sprintf('Cannot preview the kit "%s": it is neither a kit of the Toolkit nor a directory with a "manifest.json" file.', $kit));
            }

            $kitName = basename($kitDir);
            if (isset($kitDirs[$kitName])) {
                throw new \InvalidArgumentException(\sprintf('Cannot preview the kit "%s" from "%s": a kit with the same name is already previewed from "%s".', $kitName, $kitDir, $kitDirs[$kitName]));
            }

            $kitDirs[$kitName] = $kitDir;
        }

        return $kitDirs;
    }
}
