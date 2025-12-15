<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Hydration;

use App\Model\Point;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

/**
 * @template TData of Point
 * @template TDehydrated of array{point-x: float; point-y: float}
 */
class PointHydrationExtension implements HydrationExtensionInterface
{
    public function supports(string $className): bool
    {
        return is_a($className, Point::class, true);
    }

    /**
     * @param TDehydrated $value
     * @return null|TData
     */
    public function hydrate(mixed $value, string $className): ?object
    {
        return Point::create($value['px'], $value['py']);
    }

    /**
     * @param TData $object
     * @return TDehydrated
     */
    public function dehydrate(object $object): mixed
    {
        return [
            'px' => $object->x,
            'py' => $object->y,
        ];
    }
}
