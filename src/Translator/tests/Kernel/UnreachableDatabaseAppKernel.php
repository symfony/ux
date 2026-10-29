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
use Symfony\UX\Translator\Tests\Fixtures\UnreachableDatabaseLoader;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class UnreachableDatabaseAppKernel extends StableCacheDirAppKernel
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

            $container->register('unreachable_database_loader', UnreachableDatabaseLoader::class)
                ->addTag('translation.loader', ['alias' => 'db']);
        });
    }
}
