<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class TypeHelper
{
    /**
     * TODO: To remove when supporting symfony/type-info >=7.3 only.
     *
     * Traverses the whole type tree.
     *
     * @return iterable<Type>
     */
    public static function traverse(Type $type, bool $traverseComposite = true, bool $traverseWrapped = true): iterable
    {
        if (method_exists($type, 'traverse')) {
            yield from $type->traverse($traverseComposite, $traverseWrapped);
        } else {
            yield $type;

            if ($type instanceof CompositeTypeInterface && $traverseComposite) {
                foreach ($type->getTypes() as $innerType) {
                    yield $innerType;
                }

                // prevent yielding twice when having a type that is both composite and wrapped
                return;
            }

            if ($type instanceof WrappingTypeInterface && $traverseWrapped) {
                yield $type->getWrappedType();
            }
        }
    }
}
