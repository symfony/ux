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
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class TypeHelper
{
    /**
     * TODO: To remove when supporting symfony/type-info >=7.3 only.
     */
    public static function accepts(Type $type, mixed $value): bool
    {
        $parentAccepts = function (Type $_type0, mixed $_value0): bool {
            $specification = static function (Type $_type1) use (&$specification, $_value0): bool {
                if ($_type1 instanceof WrappingTypeInterface) {
                    return $_type1->wrappedTypeIsSatisfiedBy($specification);
                }

                if ($_type1 instanceof CompositeTypeInterface) {
                    return $_type1->composedTypesAreSatisfiedBy($specification);
                }

                return TypeHelper::accepts($_type1, $_value0);
            };

            return $_type0->isSatisfiedBy($specification);
        };

        if ($type instanceof Type\ArrayShapeType) {
            if (!\is_array($value)) {
                return false;
            }

            foreach ($type->getShape() as $key => $shapeValue) {
                if (!($shapeValue['optional'] ?? false) && !\array_key_exists($key, $value)) {
                    return false;
                }
            }

            foreach ($value as $key => $itemValue) {
                $valueType = $type->getShape()[$key]['type'] ?? false;

                if ($valueType && !self::accepts($valueType, $itemValue)) {
                    return false;
                }

                if (!$valueType && ($type->isSealed() || !self::accepts($type->getExtraKeyType(), $key) || !self::accepts($type->getExtraValueType(), $itemValue))) {
                    return false;
                }
            }

            return true;
        }

        // Also supports EnumType and BackedEnumType
        if ($type instanceof Type\ObjectType) {
            $className = $type->getClassName();

            return $value instanceof $className;
        }

        if ($type instanceof Type\BuiltinType) {
            return match ($type->getTypeIdentifier()) {
                TypeIdentifier::ARRAY => \is_array($value),
                TypeIdentifier::BOOL => \is_bool($value),
                TypeIdentifier::CALLABLE => \is_callable($value),
                TypeIdentifier::FALSE => false === $value,
                TypeIdentifier::FLOAT => \is_float($value),
                TypeIdentifier::INT => \is_int($value),
                TypeIdentifier::ITERABLE => is_iterable($value),
                TypeIdentifier::MIXED => true,
                TypeIdentifier::NULL => null === $value,
                TypeIdentifier::OBJECT => \is_object($value),
                TypeIdentifier::RESOURCE => \is_resource($value),
                TypeIdentifier::STRING => \is_string($value),
                TypeIdentifier::TRUE => true === $value,
                default => false,
            };
        }

        if ($type instanceof Type\CollectionType) {
            if (!$parentAccepts($type, $value)) {
                return false;
            }

            if ($type->isList() && (!\is_array($value) || !array_is_list($value))) {
                return false;
            }

            $keyType = $type->getCollectionKeyType();
            $valueType = $type->getCollectionValueType();

            if (is_iterable($value)) {
                foreach ($value as $k => $v) {
                    // key or value do not match
                    if (!self::accepts($keyType, $k) || !self::accepts($valueType, $v)) {
                        return false;
                    }
                }
            }

            return true;
        }

        if ($type instanceof Type\NullableType) {
            return null === $value || $parentAccepts($type, $value);
        }

        if ($type instanceof Type\GenericType || $type instanceof Type\TemplateType || $type instanceof Type\UnionType || $type instanceof Type\IntersectionType) {
            return $parentAccepts($type, $value);
        }

        return false;
    }

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
