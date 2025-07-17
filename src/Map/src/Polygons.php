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
 * Represents a Polygon collection.
 *
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @internal
 */
final class Polygons extends Elements
{
    public static function fromArray(array $elements): self
    {
        $elementObjects = [];

        foreach ($elements as $element) {
            $elementObjects[] = Polygon::fromArray($element);
        }

        return new self(elements: $elementObjects);
    }
}
