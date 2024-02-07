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
    public static function getEntityClass(object $entity): string
    {
        if ($entity instanceof LazyObjectInterface) {
            $entityClass = get_parent_class($entity);
            if (false === $entityClass) {
                throw new \LogicException('Parent class missing');
            }

            return $entityClass;
        }

        // @legacy for old versions of Doctrine
        if (class_exists(ClassUtils::class)) {
            return ClassUtils::getClass($entity);
        }

        return $entity::class;
    }
}
