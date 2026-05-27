<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Symfony\UX\TwigComponent\Event\PreRenderEvent;

/**
 * @method ?string        preCreateForRender(string $name, array $props = [])
 * @method PreRenderEvent startEmbeddedComponentRender(string $name, array $props, array $context, string $hostTemplateName, int $index)
 * @method void           finishEmbeddedComponentRender()
 */
interface ComponentRendererInterface
{
    /**
     * Create and render a twig component.
     */
    public function createAndRender(string $name, array $props = []): string;
}
