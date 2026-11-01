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

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\UX\LiveComponent\Form\Extension\LiveRequestCsrfTypeExtension;
use Symfony\UX\LiveComponent\Hydration\DoctrineEntityHydrationExtension;
use Symfony\UX\LiveComponent\LiveComponentBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class OptionalDependencyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('doctrine')) {
            $container->register('ux.live_component.doctrine_entity_hydration_extension', DoctrineEntityHydrationExtension::class)
                ->setArguments([new IteratorArgument([new Reference('doctrine')])]) // TODO: add support for multiple entity managers
                ->addTag(LiveComponentBundle::HYDRATION_EXTENSION_TAG)
            ;
        }

        if ($container->hasDefinition('form.type_extension.csrf')) {
            // after the CSRF extension, which defines the "csrf_token_manager" option
            $container->register('ux.live_component.form.live_request_csrf_type_extension', LiveRequestCsrfTypeExtension::class)
                ->setArguments([new Reference('request_stack')])
                ->addTag('form.type_extension', ['extended_type' => FormType::class, 'priority' => -1024])
            ;
        }

        if ($container->hasDefinition('test.client')) {
            $container->getDefinition('ux.live_component.event_subscriber')
                ->setArgument(1, true);
        }
    }
}
