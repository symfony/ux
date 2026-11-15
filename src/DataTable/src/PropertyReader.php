<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Reads a dotted path ("category.name") through objects and arrays, a null
 * value along the way giving null. A path with brackets is a property path.
 *
 * @internal
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class PropertyReader
{
    private readonly PropertyAccessorInterface $accessor;

    public function __construct(?PropertyAccessorInterface $accessor = null)
    {
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function read(mixed $row, string $path): mixed
    {
        if (!\is_array($row) && !\is_object($row)) {
            return null;
        }

        if (str_contains($path, '[')) {
            return $this->accessor->getValue($row, $path);
        }

        $value = $row;
        foreach (explode('.', str_replace('?', '', $path)) as $segment) {
            $value = match (true) {
                \is_array($value), $value instanceof \ArrayAccess => $value[$segment] ?? null,
                \is_object($value) => $this->accessor->getValue($value, $segment),
                default => null,
            };
        }

        return $value;
    }
}
