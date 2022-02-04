<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class ComponentRuntime
{
    private ComponentFactory $componentFactory;
    private ComponentRenderer $componentRenderer;

    public function __construct(ComponentFactory $componentFactory, ComponentRenderer $componentRenderer)
    {
        $this->componentFactory = $componentFactory;
        $this->componentRenderer = $componentRenderer;
    }

    public function render(string $name, array $props = []): string
    {
        return $this->componentRenderer->render(
            $this->componentFactory->create($name, $props),
            $this->componentFactory->metadataFor($name)
        );
    }
}
