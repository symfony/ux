<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Collection\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Collection\Form\CollectionTypeExtension;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 */
final class CollectionExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->registerBasicServices($container);
    }

    private function registerBasicServices(ContainerBuilder $container): void
    {
        $container
            ->register('ux.collection.collection_extension', CollectionTypeExtension::class)
            ->addTag('form.type_extension')
        ;
    }
}
