<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched at the start of the component rendering process.
 *
 * This event occurs before the component is created & mounted.
 *
 * @experimental
 */
final class PreCreateForRenderEvent extends Event
{
    private ?string $renderedString = null;

    public function __construct(
        private string $name,
        private array $props = []
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    public function setRenderedString(string $renderedString): void
    {
        $this->setRenderedString($renderedString);

        $this->stopPropagation();
    }

    public function getRenderedString(): ?string
    {
        return $this->renderedString;
    }
}
