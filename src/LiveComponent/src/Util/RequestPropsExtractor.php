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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\UX\LiveComponent\Exception\HydrationException;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LegacyLivePropMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;

/**
 * @author Nicolas Rigaud <squrious@protonmail.com>
 *
 * @internal
 */
final class RequestPropsExtractor
{
    public function __construct(private readonly LiveComponentHydrator $hydrator)
    {
    }

    /**
     * Extracts relevant props parameters from the current URL and hydrates them.
     */
    public function extract(Request $request, LiveComponentMetadata $metadata, object $component): array
    {
        $parameters = array_merge($request->attributes->all(), $request->query->all());

        if (empty($parameters)) {
            return [];
        }
        $data = [];

        foreach ($metadata->getAllLivePropsMetadata($component) as $livePropMetadata) {
            if ($queryMapping = $livePropMetadata->urlMapping()) {
                $frontendName = $livePropMetadata->calculateFieldName($component, $livePropMetadata->getName());
                if (null !== ($value = $parameters[$queryMapping->as ?? $frontendName] ?? null)) {
                    if ('' === $value) {
                        // BC layer when "symfony/type-info" is not available
                        if ($livePropMetadata instanceof LegacyLivePropMetadata) {
                            if (!$livePropMetadata->isBuiltIn() || 'array' === $livePropMetadata->getType()) {
                                $value = [];
                            }
                        } else {
                            $type = $livePropMetadata->getType();
                            if (null !== $type && (!$type->isSatisfiedBy(fn (Type $t): bool => $t instanceof BuiltinType) || $type->isIdentifiedBy(TypeIdentifier::ARRAY))) {
                                // Cast empty string to empty array for objects and arrays
                                $value = [];
                            }
                        }
                    }

                    try {
                        $hydratedValue = $this->hydrator->hydrateValue($value, $livePropMetadata, $component);

                        if ($this->isValueTypeConsistent($hydratedValue, $livePropMetadata)) {
                            // Only set data if hydrated value type is consistent with prop metadata type
                            $data[$livePropMetadata->getName()] = $hydratedValue;
                        }
                    } catch (HydrationException) {
                        // Skip hydration errors (e.g. with objects)
                    }
                }
            }
        }

        return $data;
    }

    private function isValueTypeConsistent(mixed $value, LivePropMetadata|LegacyLivePropMetadata $livePropMetadata): bool
    {
        // BC layer when "symfony/type-info" is not available
        if ($livePropMetadata instanceof LegacyLivePropMetadata) {
            $propType = $livePropMetadata->getType();

            if ($livePropMetadata->allowsNull() && null === $value) {
                return true;
            }

            return
                \in_array($propType, [null, 'mixed'])
                || $livePropMetadata->isBuiltIn() && ('\is_'.$propType)($value)
                || !$livePropMetadata->isBuiltIn() && $value instanceof $propType;
        } else {
            $type = $livePropMetadata->getType();

            return null === $type || TypeHelper::accepts($type, $value);
        }
    }
}
