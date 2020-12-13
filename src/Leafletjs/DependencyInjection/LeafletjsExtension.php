<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Leafletjs\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Leafletjs\Builder\LeafletBuilder;
use Symfony\UX\Leafletjs\Builder\LeafletBuilderInterface;
use Symfony\UX\Leafletjs\Twig\LeafletExtension;
use Twig\Environment;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Michael Cramer <michael@bigmichi1.de>
 *
 * @internal
 */
class LeafletjsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->setDefinition('leafletjs.builder', new Definition(LeafletBuilder::class))
            ->setPublic(false)
        ;

        $container
            ->setAlias(LeafletBuilderInterface::class, 'leafletjs.builder')
            ->setPublic(false)
        ;

        if (class_exists(Environment::class)) {
            $container
                ->setDefinition('leafletjs.twig_extension', new Definition(LeafletExtension::class))
                ->addTag('twig.extension')
                ->setPublic(false)
            ;
        }
    }
}
