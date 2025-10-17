<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AddControllerTagToLiveComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('twig.component') as $id => $component) {
            if (!($component[0]['live'] ?? false)) {
                continue;
            }

            $definition = $container->getDefinition($id);

            if (!$definition->hasTag('controller.service_arguments')) {
                $definition->addTag('controller.service_arguments');
            }
        }
    }
}
