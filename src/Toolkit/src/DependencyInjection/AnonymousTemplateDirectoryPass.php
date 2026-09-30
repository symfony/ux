<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Gives "ux:install" the directory TwigComponent looks into for anonymous components.
 *
 * TwigComponent exposes that directory as an argument of its template finder only, not as a parameter.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 *
 * @internal
 */
final class AnonymousTemplateDirectoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ux.twig_component.component_template_finder') || !$container->hasDefinition('.ux_toolkit.command.install')) {
            return;
        }

        $container->getDefinition('.ux_toolkit.command.install')->setArgument(
            '$anonymousTemplateDirectory',
            $container->getDefinition('ux.twig_component.component_template_finder')->getArgument(1),
        );
    }
}
