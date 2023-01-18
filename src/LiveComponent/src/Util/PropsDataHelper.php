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

/**
 * Helps organize the dehydrated props and data.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class PropsDataHelper
{
    private const IDENTIFIER_PROP_KEY = '@id';

    /**
     * Transforms the dehydrated data into the form used by the frontend.
     *
     * For example, suppose we have the following keys and their dehydrated data:
     *
     *      * product = 5
     *      * product.name = "foo"
     *
     * This will transform the data into:
     *
     *     product: [
     *
     *         @id: 5,
     *         name: foo
     *     ]
     */
    public static function expandToFrontendArray(array $propertyPathData): array
    {
        $props = [];
        foreach ($propertyPathData as $propertyPath => $value) {
            // skip nested paths for now
            if (str_contains($propertyPath, '.')) {
                continue;
            }

            unset($propertyPathData[$propertyPath]);

            $props[$propertyPath] = $value;
        }

        $transformedToArrayPropNames = [];
        foreach ($propertyPathData as $propertyPath => $value) {
            $propertyPaths = explode('.', $propertyPath);
            $rootPropertyName = array_shift($propertyPaths);

            // transform into an array with "@id" as the key, but just once
            if (!\in_array($rootPropertyName, $transformedToArrayPropNames, true)) {
                $props[$rootPropertyName] = [
                    self::IDENTIFIER_PROP_KEY => $props[$rootPropertyName],
                ];
                $transformedToArrayPropNames[] = $rootPropertyName;
            }

            self::addDataToPropertyPaths(
                $propertyPaths,
                $value,
                $props[$rootPropertyName],
            );
        }

        return $props;
    }

    /**
     * Transforms the data from the frontend into the form used by the backend.
     * Does the exact reverse of the expandPropertyPathDataToArray method.
     *
     * For example, suppose we have the following keys and their dehydrated data:
     *
     *     'product' => [
     *         '@id' => 5,
     *         'name': 'foo',
     *     ]
     *
     * This will transform the data into:
     *
     *      [
     *          'product' => 5,
     *          'product.name' => 'foo',
     *      ]
     */
    public static function flattenToBackendArray(array $props): array
    {
        $propertyPathData = [];

        foreach ($props as $key => $value) {
            // scalar key => entire value is the "identity"
            // array with no "@id" key => entire array is the "identity"
            if (!\is_array($value) || !\array_key_exists(self::IDENTIFIER_PROP_KEY, $value)) {
                $propertyPathData[$key] = $value;

                continue;
            }

            $propertyPathData[$key] = $value[self::IDENTIFIER_PROP_KEY];

            unset($value[self::IDENTIFIER_PROP_KEY]);

            self::addPropertyPathsFromData(
                $key,
                $value,
                $propertyPathData
            );
        }

        return $propertyPathData;
    }

    private static function addDataToPropertyPaths(array $propertyPaths, mixed $value, array &$targetData): void
    {
        $firstKey = array_shift($propertyPaths);

        // no more keys left? This is the last key: set it and return
        if (0 === \count($propertyPaths)) {
            $targetData[$firstKey] = $value;

            return;
        }

        if (!isset($targetData[$firstKey])) {
            $targetData[$firstKey] = [];
        }

        self::addDataToPropertyPaths(
            $propertyPaths,
            $value,
            $targetData[$firstKey]
        );
    }

    private static function addPropertyPathsFromData(string $currentPropertyPath, array $values, array &$targetData): void
    {
        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                self::addPropertyPathsFromData(
                    $currentPropertyPath.'.'.$key,
                    $value,
                    $targetData
                );

                continue;
            }

            $targetData[$currentPropertyPath.'.'.$key] = $value;
        }
    }
}
