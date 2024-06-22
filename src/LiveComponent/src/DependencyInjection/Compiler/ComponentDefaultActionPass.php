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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentDefaultActionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('twig.component') as $id => $component) {
            if (!($component[0]['live'] ?? false)) {
                continue;
            }

            if (!$class = $container->getDefinition($id)->getClass()) {
                throw new \LogicException(\sprintf('Live component service "%s" must have a class.', $id));
            }

            $defaultAction = trim($component[0]['default_action'] ?? '__invoke', '()');

            if (!method_exists($class, $defaultAction)) {
                throw new \LogicException(\sprintf('Live component "%s" requires the default action method "%s".%s', $class, $defaultAction, '__invoke' === $defaultAction ? ' Either add this method or use the DefaultActionTrait' : ''));
            }
        }
    }
}
