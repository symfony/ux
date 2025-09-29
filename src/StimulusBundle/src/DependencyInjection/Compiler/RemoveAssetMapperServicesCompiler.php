<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class RemoveAssetMapperServicesCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('asset_mapper')) {
            $container->removeDefinition('stimulus.ux_controllers_twig_runtime');
            $container->removeDefinition('stimulus.asset_mapper.controllers_map_generator');
            $container->removeDefinition('stimulus.asset_mapper.loader_javascript_compiler');
        }
    }
}
