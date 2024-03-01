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

use Doctrine\Common\Util\ClassUtils as LegacyClassUtils;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * @internal
 */
final class ClassUtil
{
    public static function getEntityClass(object $entity): string
    {
        if ($entity instanceof LazyObjectInterface) {
            return get_parent_class($entity) ?: $entity::class;
        }

        // @legacy for old versions of Doctrine
        if (class_exists(LegacyClassUtils::class)) {
            return LegacyClassUtils::getClass($entity);
        }

        return $entity::class;
    }
}
