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
class DehydratedProps
{
    /**
     * Array keyed by the frontend LiveProp name (e.g. product) and set to the dehydrated value.
     *
     * @var array<string, mixed>
     */
    private array $propValues = [];

    /**
     * Array keyed by the full nested path (e.g. "product.name") and set to the dehydrated value.
     *
     * @var array<string, mixed>
     */
    private array $nestedPathValues = [];

    /**
     * Create this object from the extra "props" identifiers passed to/from the frontend.
     */
    public static function createFromPropsArray(array $propIdentifierValues): self
    {
        $props = new static();
        $props->propValues = $propIdentifierValues;

        return $props;
    }

    /**
     * Create this object from the "updated" props passed from the frontend to the backend.
     */
    public static function createFromUpdatedArray(array $updatedPaths): self
    {
        $props = new static();
        foreach ($updatedPaths as $frontendName => $dehydratedValue) {
            if (str_contains($frontendName, '.')) {
                [$frontendName, $nestedPath] = explode('.', $frontendName, 2);
                $props->addNestedProp($frontendName, $nestedPath, $dehydratedValue);

                continue;
            }

            $props->addPropValue($frontendName, $dehydratedValue);
        }

        return $props;
    }

    public function addPropValue(string $frontendName, mixed $dehydratedValue): void
    {
        $this->propValues[$frontendName] = $dehydratedValue;
    }

    public function addNestedProp(string $frontendName, string $nestedPath, mixed $pathValue): void
    {
        $fullPath = $frontendName.'.'.$nestedPath;
        $this->nestedPathValues[$fullPath] = $pathValue;
    }

    public function removePropValue(string $key): void
    {
        unset($this->propValues[$key]);
    }

    public function getPropValue(string $propName, mixed $default = null): mixed
    {
        if (!$this->hasPropValue($propName)) {
            return $default;
        }

        return $this->propValues[$propName];
    }

    public function hasPropValue(string $propName): bool
    {
        return \array_key_exists($propName, $this->propValues);
    }

    public function hasNestedPathValue(string $propName, string $nestedPath): bool
    {
        $fullPath = $propName.'.'.$nestedPath;

        return \array_key_exists($fullPath, $this->nestedPathValues);
    }

    public function getNestedPathValue(string $propName, string $nestedPath): mixed
    {
        if (!$this->hasNestedPathValue($propName, $nestedPath)) {
            throw new \InvalidArgumentException(sprintf('The nested path "%s.%s" does not exist.', $propName, $nestedPath));
        }

        $fullPath = $propName.'.'.$nestedPath;

        return $this->nestedPathValues[$fullPath];
    }

    public function getNestedPathsForProperty(string $prop): array
    {
        $nestedPaths = [];
        foreach ($this->nestedPathValues as $fullPath => $value) {
            if (str_starts_with($fullPath, $prop.'.')) {
                $nestedPaths[] = substr($fullPath, \strlen($prop) + 1);
            }
        }

        return $nestedPaths;
    }

    public function calculateUnexpectedNestedPathsForProperty(string $prop, array $expectedNestedPaths): array
    {
        $clone = clone $this;
        foreach ($expectedNestedPaths as $nestedPath) {
            unset($clone->nestedPathValues[$prop.'.'.$nestedPath]);
        }

        return $clone->getNestedPathsForProperty($prop);
    }

    public function getProps(): array
    {
        return $this->propValues;
    }

    public function getNestedProps(): array
    {
        return $this->nestedPathValues;
    }
}
