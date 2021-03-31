<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class TurboBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new class() implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                if (!$container->hasDefinition('turbo.broadcaster.imux')) {
                    return;
                }
                if (!$container->getDefinition('turbo.broadcaster.imux')->getArgument(0)->getValues()) {
                    $container->removeDefinition('turbo.doctrine.event_listener');
                }
            }
        }, PassConfig::TYPE_BEFORE_REMOVING);
    }
}
