<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Kernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Translator\Tests\Fixtures\EntityMetadataCacheWarmer;
use Symfony\UX\Translator\Tests\Fixtures\EntityMetadataRegistry;
use Symfony\UX\Translator\Tests\Fixtures\EntityTranslationsLoader;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class EntityTranslationsAppKernel extends StableCacheDirAppKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(static function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'translator' => [
                    'paths' => ['%kernel.project_dir%/tests/Fixtures/database_translations'],
                ],
            ]);

            $container->register('entity_metadata_registry', EntityMetadataRegistry::class);

            $container->register('entity_translations_loader', EntityTranslationsLoader::class)
                ->addArgument(new Reference('entity_metadata_registry'))
                ->addTag('translation.loader', ['alias' => 'db']);

            $container->register('entity_metadata_cache_warmer', EntityMetadataCacheWarmer::class)
                ->addArgument(new Reference('entity_metadata_registry'))
                ->addTag('kernel.cache_warmer', ['priority' => 1000]);
        });
    }
}
