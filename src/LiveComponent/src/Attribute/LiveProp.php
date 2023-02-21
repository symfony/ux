<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Attribute;

/**
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class LiveProp
{
    /**
     * Mark that a property could be completely changed to another. Usage:
     *      #[LiveProp(writable: [LiveProp::IDENTITY])].
     */
    public const IDENTITY = '@identity';

    /** @var bool|string[] */
    private bool|array $writable;

    private ?string $hydrateWith;

    private ?string $dehydrateWith;

    /**
     * The "frontend" field name that should be used for this property.
     *
     * This can be used, for example, to have a property called "foo", which actually
     * maps to a frontend data model called "bar".
     *
     * If you pass a string that ends in () - like "getFieldName()" - that
     * method on the component will be called to determine this.
     */
    private ?string $fieldName;

    public function __construct(
        bool|array $writable = false,
        ?string $hydrateWith = null,
        ?string $dehydrateWith = null,
        ?string $fieldName = null
    ) {
        $this->writable = $writable;
        $this->hydrateWith = $hydrateWith;
        $this->dehydrateWith = $dehydrateWith;
        $this->fieldName = $fieldName;
    }

    /**
     * @internal
     */
    public function isIdentityWritable(): bool
    {
        if (\is_bool($this->writable)) {
            return $this->writable;
        }

        return \in_array(self::IDENTITY, $this->writable, true);
    }

    /**
     * @internal
     */
    public function writablePaths(): array
    {
        if (\is_bool($this->writable)) {
            return [];
        }

        return array_values(array_filter($this->writable, function ($item) {
            return self::IDENTITY !== $item;
        }));
    }

    /**
     * @internal
     */
    public function hydrateMethod(): ?string
    {
        return $this->hydrateWith ? trim($this->hydrateWith, '()') : null;
    }

    /**
     * @internal
     */
    public function dehydrateMethod(): ?string
    {
        return $this->dehydrateWith ? trim($this->dehydrateWith, '()') : null;
    }

    /**
     * @internal
     */
    public function calculateFieldName(object $component, string $fallback): string
    {
        if (!$this->fieldName) {
            return $fallback;
        }

        if (str_ends_with($this->fieldName, '()')) {
            return $component->{trim($this->fieldName, '()')}();
        }

        return $this->fieldName;
    }
}
