<?php

declare(strict_types=1);

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
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Jacob Tobiasz <jakub.tobiasz@icloud.com>
 *
 * @internal
 */
final class LiveComponentTagPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('live.component') as $id => $tags) {
            foreach ($tags as $tag) {
                $liveComponentService = $container->getDefinition($id);
                $liveComponentService->addTag('twig.component', [
                    'key' => $tag['key'] ?? throw new InvalidArgumentException('The "key" attribute is required for the "live.component" tag.'),
                    'template' => $tag['template'] ?? throw new InvalidArgumentException('The "template" attribute is required for the "live.component" tag.'),
                    'expose_public_props' => $tag['expose_public_props'] ?? true,
                    'attributes_var' => $tag['attributes_var'] ?? 'attributes',
                    'default_action' => $tag['default_action'] ?? null,
                    'live' => true,
                    'csrf' => $tag['csrf'] ?? true,
                    'route' => $tag['route'] ?? 'ux_live_component',
                    'method' => $tag['method'] ?? 'post',
                    'url_reference_type' => $tag['url_reference_type'] ?? UrlGeneratorInterface::ABSOLUTE_PATH,
                ]);
                $liveComponentService->addTag('controller.service_arguments');
            }
        }
    }
}
