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

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
class ComponentSlot
{
    public AttributeBag $attributes;

    protected string $contents;

    public function __construct(string $contents = '', array $attributes = [])
    {
        $this->contents = $contents;

        $this->withAttributes($attributes);
    }

    public function withAttributes(array $attributes): self
    {
        $this->attributes = new AttributeBag($attributes);

        return $this;
    }

    public function toHtml(): string
    {
        return $this->contents;
    }

    public function isEmpty(): bool
    {
        return $this->contents === '';
    }

    public function __toString()
    {
        return $this->toHtml();
    }
}