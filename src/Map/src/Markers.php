<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map;

/**
 * Represents a Marker collection.
 *
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @internal
 */
final class Markers extends Elements
{
    public static function fromArray(array $elements): self
    {
        $elementObjects = [];

        foreach ($elements as $element) {
            $elementObjects[] = Marker::fromArray($element);
        }

        return new self(elements: $elementObjects);
    }
}
