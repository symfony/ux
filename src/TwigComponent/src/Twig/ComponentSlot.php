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

use Symfony\UX\TwigComponent\ComponentAttributes;

/**
 * thanks to @giorgiopogliani!
 * This file is inspired by: https://github.com/giorgiopogliani/twig-components.
 *
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
class ComponentSlot
{
    public ComponentAttributes $attributes;

    protected string $contents;

    public function __construct(string $contents = '', array $attributes = [])
    {
        $this->contents = $contents;

        $this->withAttributes($attributes);
    }

    public function withAttributes(array $attributes): self
    {
        $this->attributes = new ComponentAttributes($attributes);

        return $this;
    }

    public function withContext(array $contexts): void
    {
        $content = $this->contents;

        foreach ($contexts as $key => $value) {
            $content = str_replace("<slot_value name=\"$key\"/>", $value, $content);
        }

        $this->contents = $content;
    }

    public function toHtml(): string
    {
        return $this->contents;
    }

    public function isEmpty(): bool
    {
        return '' === $this->contents;
    }

    public function __toString()
    {
        return $this->toHtml();
    }
}
