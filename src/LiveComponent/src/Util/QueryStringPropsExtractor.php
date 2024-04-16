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
use Symfony\UX\LiveComponent\Exception\HydrationException;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;

/**
 * @author Nicolas Rigaud <squrious@protonmail.com>
 *
 * @internal
 */
final class QueryStringPropsExtractor
{
    public function __construct(private readonly LiveComponentHydrator $hydrator)
    {
    }

    /**
     * Extracts relevant query parameters from the current URL and hydrates them.
     */
    public function extract(Request $request, LiveComponentMetadata $metadata, object $component): array
    {
        $query = $request->query->all();

        if (empty($query)) {
            return [];
        }
        $data = [];

        foreach ($metadata->getAllLivePropsMetadata($component) as $livePropMetadata) {
            if ($queryMapping = $livePropMetadata->urlMapping()) {
                $frontendName = $livePropMetadata->calculateFieldName($component, $livePropMetadata->getName());
                if (null !== ($value = $query[$queryMapping->as ?? $frontendName] ?? null)) {
                    if ('' === $value && null !== $livePropMetadata->getType() && (!$livePropMetadata->isBuiltIn() || 'array' === $livePropMetadata->getType())) {
                        // Cast empty string to empty array for objects and arrays
                        $value = [];
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

    private function isValueTypeConsistent(mixed $value, LivePropMetadata $livePropMetadata): bool
    {
        $propType = $livePropMetadata->getType();

        if ($livePropMetadata->allowsNull() && null === $value) {
            return true;
        }

        return
            \in_array($propType, [null, 'mixed'])
            || $livePropMetadata->isBuiltIn() && ('\is_'.$propType)($value)
            || !$livePropMetadata->isBuiltIn() && $value instanceof $propType;
    }
}
