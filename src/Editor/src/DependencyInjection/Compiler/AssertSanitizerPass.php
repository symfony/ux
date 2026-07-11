<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AssertSanitizerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('ux_editor.html.sanitize_required') || !$container->getParameter('ux_editor.html.sanitize_required')) {
            return;
        }
        if (!$container->hasDefinition('html_sanitizer.sanitizer.default') && !$container->hasAlias('html_sanitizer.sanitizer.default')) {
            throw new \RuntimeException('ux_editor.html.sanitize_required is true but no "html_sanitizer.sanitizer.default" service is registered. Install symfony/html-sanitizer or set sanitize_required: false.');
        }
    }
}
