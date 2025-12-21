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

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 *
 * @experimental
 */
class UxTranslatorExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/../config')));
        $loader->load('services.php');

        $includedDomains = [];
        $excludedDomains = [];

        if (isset($config['domains'])) {
            if ('inclusive' === $config['domains']['type']) {
                $includedDomains = $config['domains']['elements'];
            } else {
                $excludedDomains = $config['domains']['elements'];
            }
        }

        $cacheWarmerDefinition = $container->getDefinition('ux.translator.cache_warmer.translations_cache_warmer');
        $cacheWarmerDefinition->setArgument(2, $config['dump_directory']);
        $cacheWarmerDefinition->setArgument(3, $config['dump_typescript']);
        $cacheWarmerDefinition->setArgument(4, $includedDomains);
        $cacheWarmerDefinition->setArgument(5, $excludedDomains);
        $cacheWarmerDefinition->setArgument(6, $config['keys_patterns'] ?? []);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/ux-translator',
                    '%kernel.project_dir%/var/translations' => 'var/translations',
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
