<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Gives the stylesheet dumper a template iterator that lists the templates again each time it is asked.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class TemplateIteratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('twig.template_iterator') || !$container->hasDefinition('ux_css.stylesheet_dumper')) {
            return;
        }

        // Twig's iterator keeps the list it found first, which a long-running process cannot trust
        $iterator = clone $container->getDefinition('twig.template_iterator');
        $iterator->setShared(false);
        $container->setDefinition('.ux_css.template_iterator', $iterator);

        $templates = new ServiceClosureArgument(new Reference('.ux_css.template_iterator'));
        $container->getDefinition('ux_css.stylesheet_dumper')->replaceArgument(3, $templates);
    }
}
