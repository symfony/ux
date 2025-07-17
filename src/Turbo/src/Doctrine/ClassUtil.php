<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Doctrine;

use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * @internal
 */
final class ClassUtil
{
    /**
     * @return class-string
     */
    public static function getEntityClass(object $entity): string
    {
        // Doctrine proxies (old versions)
        if (str_contains($entity::class, 'Proxies\\__CG__')) {
            return get_parent_class($entity) ?: $entity::class;
        }

        if ($entity instanceof LazyObjectInterface) {
            return get_parent_class($entity) ?: $entity::class;
        }

        return $entity::class;
    }
}
