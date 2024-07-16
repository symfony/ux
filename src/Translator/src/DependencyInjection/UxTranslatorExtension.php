<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Translator\Dumper\Front\FrontFileDumperInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 *
 * @experimental
 */
class UxTranslatorExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/../config')));
        $loader->load('services.php');

        $dumpDir = $config['dump_directory'];
        $assetMapperMode = $config['asset_mapper_mode'];

        $translationDumper = $container->getDefinition('ux.translator.translations_dumper');
        $translationDumper->setArgument(0, $dumpDir);
        if ($assetMapperMode) {
            $translationDumper->addMethodCall('addDumper', [$container->getDefinition('ux.translator.translations_dumper.domain_module')]);
        }

        $translationDumper->addMethodCall('addDumper', [$container->getDefinition('ux.translator.translations_dumper.message_constant')]);
        $translationDumper->addMethodCall('addDumper', [$container->getDefinition('ux.translator.translations_dumper.configuration')]);

        foreach ($container->findTaggedServiceIds('ux.translator.front_translations_dumper') as $id => $attributes) {
            $container->getDefinition($id)->addMethodCall('setDumpDir', [$dumpDir]);
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $config = $this->processConfiguration(new Configuration(), $container->getExtensionConfig($this->getAlias()));

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/ux-translator',
                    $config['dump_directory'] => 'var/translations',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
