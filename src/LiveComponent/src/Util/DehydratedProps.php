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
    private array $identifierValues = [];
    private array $writablePathValues = [];

    public function __construct(private bool $allowMissingIdentifierValues = false)
    {
    }

    public static function createFromArray(array $props): self
    {
        $dehydratedOriginalProps = new static();
        foreach ($props as $frontendName => $value) {
            if (!\is_array($value) || !isset($value['@id'])) {
                $dehydratedOriginalProps->addIdentifierValue($frontendName, $value);

                continue;
            }

            // grab the identifier value
            $dehydratedOriginalProps->addIdentifierValue($frontendName, $value['@id']);
            unset($value['@id']);
            $dehydratedOriginalProps->writablePathValues[$frontendName] = $value;
        }

        return $dehydratedOriginalProps;
    }

    /**
     * Created from a flattened array of props, where writable paths are keys with ".".
     *
     * For example:
     *  [
     *     'foo' => 'bar',
     *     'foo.bar' => 'baz',
     *  ]
     */
    public static function createFromUpdatedArray(array $props): self
    {
        $dehydratedOriginalProps = new static(allowMissingIdentifierValues: true);
        foreach ($props as $frontendName => $value) {
            // if frontendName contains ".", it's a writable path
            // else it's an identifier
            // set appropriately
            if (str_contains($frontendName, '.')) {
                [$propName, $writablePath] = explode('.', $frontendName, 2);
                $dehydratedOriginalProps->addWritablePathValue($propName, $writablePath, $value);
            } else {
                $dehydratedOriginalProps->addIdentifierValue($frontendName, $value);
            }
        }

        return $dehydratedOriginalProps;
    }

    public function addIdentifierValue(string $frontendName, mixed $dehydratedValue): void
    {
        $this->identifierValues[$frontendName] = $dehydratedValue;
    }

    public function addWritablePathValue(string $frontendName, string $writablePath, mixed $pathValue): void
    {
        if (!\array_key_exists($frontendName, $this->identifierValues) && !$this->allowMissingIdentifierValues) {
            throw new \InvalidArgumentException(sprintf('The identifier for the property "%s" has not been set yet.', $frontendName));
        }

        if (!isset($this->writablePathValues[$frontendName])) {
            $this->writablePathValues[$frontendName] = [];
        }

        $this->writablePathValues[$frontendName] = self::setValueDeeplyOntoArray(
            $this->writablePathValues[$frontendName],
            $writablePath,
            $pathValue
        );
    }

    public function toArray(): array
    {
        $props = $this->identifierValues;
        foreach ($this->writablePathValues as $propName => $writablePathValues) {
            if (!\array_key_exists($propName, $props)) {
                throw new \InvalidArgumentException(sprintf('The identifier for the property "%s" has not been set yet.', $propName));
            }

            $props[$propName] = array_merge(
                ['@id' => $this->identifierValues[$propName]],
                $writablePathValues
            );
        }

        return $props;
    }

    public function removeIdentifierValue(string $key): void
    {
        unset($this->identifierValues[$key]);
    }

    public function getIdentifierValue(string $propName, mixed $default = null): mixed
    {
        if (!$this->hasIdentifierValue($propName)) {
            return $default;
        }

        return $this->identifierValues[$propName];
    }

    public function hasIdentifierValue(string $propName): bool
    {
        return \array_key_exists($propName, $this->identifierValues);
    }

    public function hasWritablePathValue(string $propName, string $writablePath): bool
    {
        if (!\array_key_exists($propName, $this->writablePathValues)) {
            return false;
        }

        $currentArray = $this->writablePathValues[$propName];
        $parts = explode('.', $writablePath);
        foreach ($parts as $part) {
            if (!\array_key_exists($part, $currentArray)) {
                return false;
            }

            $currentArray = $currentArray[$part];
        }

        return true;
    }

    public function getWritablePathValue(string $propName, string $writablePath): mixed
    {
        if (!$this->hasWritablePathValue($propName, $writablePath)) {
            throw new \InvalidArgumentException(sprintf('The writable path "%s.%s" does not exist.', $propName, $writablePath));
        }

        $currentValue = $this->writablePathValues[$propName];
        $parts = explode('.', $writablePath);
        foreach ($parts as $part) {
            if (!\array_key_exists($part, $currentValue)) {
                return false;
            }

            $currentValue = $currentValue[$part];
        }

        return $currentValue;
    }

    public function getWritablePathsForProperty(string $prop): array
    {
        if (!\array_key_exists($prop, $this->writablePathValues)) {
            return [];
        }

        $paths = $this->writablePathValues[$prop];

        return $this->flattenArray($paths);
    }

    public function calculateUnexpectedWritablePathsForProperty(string $prop, array $writablePaths): array
    {
        $clone = clone $this;
        foreach ($writablePaths as $writablePath) {
            $clone->removeWritablePathValue($prop, $writablePath);
        }

        return $clone->getWritablePathsForProperty($prop);
    }

    private static function setValueDeeplyOntoArray(array $array, string $path, mixed $value): array
    {
        if (!str_contains($path, '.')) {
            $array[$path] = $value;

            return $array;
        }

        [$firstPath, $remainingPath] = explode('.', $path, 2);
        if (!isset($array[$firstPath])) {
            $array[$firstPath] = [];
        }

        $array[$firstPath] = self::setValueDeeplyOntoArray($array[$firstPath], $remainingPath, $value);

        return $array;
    }

    /**
     * Transforms the multi-dimensional array into a flat array of paths
     * separated by ".".
     */
    private function flattenArray(mixed $paths, string $prefix = ''): array
    {
        $flattened = [];
        foreach ($paths as $key => $value) {
            if (\is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenArray($value, $key.'.'));
            } else {
                $flattened[] = $prefix.$key;
            }
        }

        return $flattened;
    }

    private function removeWritablePathValue(string $prop, mixed $writablePath): void
    {
        if (!\array_key_exists($prop, $this->writablePathValues)) {
            return;
        }

        $currentArray = &$this->writablePathValues[$prop];
        $parts = explode('.', $writablePath);
        $count = 0;
        foreach ($parts as $part) {
            ++$count;
            if (!\array_key_exists($part, $currentArray)) {
                return;
            }

            if ($count === \count($parts)) {
                unset($currentArray[$part]);

                return;
            }
            $currentArray = &$currentArray[$part];
        }
    }
}
