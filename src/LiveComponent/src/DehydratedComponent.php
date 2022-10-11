<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

/**
 * @experimental
 */
class DehydratedComponent
{
    private const CHECKSUM_KEY = '_checksum';
    private const ATTRIBUTES_KEY = '_attributes';
    private const EXPOSED_PROP_KEY = '_id';

    private ?string $checksum = null;

    /**
     * props & data, for objects, should be set to the identifier (e.g. entity id).
     * Any "exposed" data should be passed to exposedData.
     *
     * For example, if the payload is { user: { _id: 5, firstName: Ryan } }
     * where "_id" is a prop (read-only) and firstName is "exposed", then:
     *      $props = ['user' => 5]
     *      $data = []
     *      $exposedData = ['user' => ['firstName' => 'Ryan']]
     */
    public function __construct(
        private array $props,
        private array $data,
        private array $exposedData,
        private array $attributes,
        private string $secret
    ) {
    }

    public static function createFromCombinedData(array $data, array $propNames, string $secret): self
    {
        unset($data[self::CHECKSUM_KEY]);
        $attributes = $data[self::ATTRIBUTES_KEY] ?? [];
        unset($data[self::ATTRIBUTES_KEY]);

        // normalize values that are a mix of key identifier + exposed data
        // e.g. [_id: 5, name: 'foo']
        //      data = 5
        //      exposeData = [name: foo]
        $exposedData = [];
        foreach ($data as $key => $value) {
            if (!\is_array($value) || !isset($value[self::EXPOSED_PROP_KEY])) {
                continue;
            }

            $data[$key] = $value[self::EXPOSED_PROP_KEY];
            unset($value[self::EXPOSED_PROP_KEY]);
            $exposedData[$key] = $value;
        }

        // separate data between props and data
        $props = [];
        foreach ($data as $key => $value) {
            if (\in_array($key, $propNames, true)) {
                $props[$key] = $value;
                unset($data[$key]);
            }
        }

        return new self($props, $data, $exposedData, $attributes, $secret);
    }

    public function getProps(bool $includeNonComponentKeys = true): array
    {
        $props = $this->structureKeyForExposedData($this->props);

        if ($includeNonComponentKeys) {
            $props += [
                self::CHECKSUM_KEY => $this->getChecksum(),
            ];

            if ($this->attributes) {
                $props[self::ATTRIBUTES_KEY] = $this->attributes;
            }
        }

        return $props;
    }

    public function getData(): array
    {
        $data = $this->structureKeyForExposedData($this->data);

        return array_merge_recursive($data, $this->exposedData);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns all the props and data combined.
     */
    public function all(): array
    {
        return array_merge_recursive($this->getProps(), $this->getData());
    }

    /**
     * Returns just the props & data intended to hydrate the component.
     *
     * This does *not* include special keys like the checksum or attributes.
     */
    public function allForComponent(): array
    {
        return array_merge_recursive(
            $this->getProps(includeNonComponentKeys: false),
            $this->getData()
        );
    }

    public function isChecksumValid(array $data): bool
    {
        if (!\array_key_exists(self::CHECKSUM_KEY, $data)) {
            return false;
        }

        return hash_equals($this->getChecksum(), $data[self::CHECKSUM_KEY]);
    }

    public function has(string $key): bool
    {
        $data = $this->allForComponent();

        return \array_key_exists($key, $data);
    }

    public function get(string $key): mixed
    {
        if (\array_key_exists($key, $this->props)) {
            return $this->props[$key];
        }

        if (\array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        throw new \InvalidArgumentException(sprintf('No data for key "%s"', $key));
    }

    public function hasExposed(string $key): bool
    {
        return \array_key_exists($key, $this->exposedData);
    }

    public function getExposed(string $key): array
    {
        if (!$this->hasExposed($key)) {
            throw new \InvalidArgumentException(sprintf('No exposed data for key "%s"', $key));
        }

        return $this->exposedData[$key];
    }

    private function getChecksum(): string
    {
        $props = $this->props;
        if (null === $this->checksum) {
            // sort so it is always consistent (frontend could have re-ordered data)
            ksort($props);

            $this->checksum = base64_encode(hash_hmac('sha256', http_build_query($props), $this->secret, true));
        }

        return $this->checksum;
    }

    /**
     * Restructures "value" to the [_id: value] format if it has exposed data.
     */
    private function structureKeyForExposedData(array $values): array
    {
        foreach ($values as $key => $value) {
            if (\array_key_exists($key, $this->exposedData)) {
                $values[$key] = [self::EXPOSED_PROP_KEY => $value];
            }
        }

        return $values;
    }
}
