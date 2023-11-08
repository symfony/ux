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

use Symfony\UX\LiveComponent\Metadata\UrlMapping;

/**
 * An attribute to mark a property as a "LiveProp".
 *
 * @see https://symfony.com/bundles/ux-live-component/current/index.html#liveprops-stateful-component-properties
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class LiveProp
{
    /**
     * Mark that a property could be completely changed to another. Usage:
     *      #[LiveProp(writable: [LiveProp::IDENTITY])].
     */
    public const IDENTITY = '@identity';

    public function __construct(
        /**
         * If true, this property can be changed by the frontend.
         *
         * Or set to an array of paths within this object/array
         * that are writable.
         *
         * @var bool|string[]
         */
        private bool|array $writable = false,

        /**
         * Method to call to hydrate this property.
         */
        private ?string $hydrateWith = null,

        /**
         * Method to call to dehydrate this property.
         */
        private ?string $dehydrateWith = null,

        /**
         * If true, the serializer will be used to dehydrate then hydrate
         * this property.
         *
         * Incompatible with hydrateWith and dehydrateWith.
         */
        private bool $useSerializerForHydration = false,

        /**
         * @var array<string, mixed>
         */
        private array $serializationContext = [],

        /**
         * The "frontend" field name that should be used for this property.
         *
         * This can be used, for example, to have a property called "foo", which
         * actually maps to a frontend data model called "bar".
         *
         * If you pass a string that ends in () - like "getFieldName()" - that
         * method on the component will be called to determine this.
         */
        private ?string $fieldName = null,

        /**
         * The format to be used if the value is a DateTime of some sort.
         *
         * For example: 'Y-m-d H:i:s'. If this property is writable, set this
         * to the format that your frontend field will use/set.
         */
        private ?string $format = null,

        /**
         * If true, while a parent component is re-rendering, if the parent
         * passes in this prop and it changed from the value used when
         * originally rendering this child, the value in the child will be
         * updated to match the new value and the child will be re-rendered.
         */
        private bool $updateFromParent = false,

        /**
         * A hook that will be called after the property is updated.
         *
         * Set it to a method name on the Live Component that should be called.
         * The old value of the property will be passed as an argument to it.
         *
         * @var string|string[]|null
         */
        private string|array|null $onUpdated = null,

        /**
         * Whether to synchronize this property with a query parameter
         * in the URL. Pass true to configure the mapping automatically, or a
         * {@see UrlMapping} instance to configure the mapping.
         */
        private bool|UrlMapping $url = false,

        /**
         * A hook that will be called when this LiveProp is used.
         *
         * Allows to modify the LiveProp options depending on the context. The
         * original LiveProp attribute instance will be passed as an argument to
         * it.
         *
         * @var string|null
         */
        private ?string $modifier = null,
    ) {
        self::validateHydrationStrategy($this);

        if (true === $url) {
            $this->url = new UrlMapping();
        }
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

    public function withWritable(bool|array $writable): self
    {
        $clone = clone $this;
        $clone->writable = $writable;

        return $clone;
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

    public function withHydrateWith(?string $hydrateWith): self
    {
        $clone = clone $this;
        $clone->hydrateWith = $hydrateWith;

        self::validateHydrationStrategy($clone);

        return $clone;
    }

    /**
     * @internal
     */
    public function dehydrateMethod(): ?string
    {
        return $this->dehydrateWith ? trim($this->dehydrateWith, '()') : null;
    }

    public function withDehydrateWith(?string $dehydrateWith): self
    {
        $clone = clone $this;
        $clone->dehydrateWith = $dehydrateWith;

        self::validateHydrationStrategy($clone);

        return $clone;
    }

    /**
     * @internal
     */
    public function useSerializerForHydration(): bool
    {
        return $this->useSerializerForHydration;
    }

    public function withUseSerializerForHydration(bool $userSerializerForHydration): self
    {
        $clone = clone $this;
        $clone->useSerializerForHydration = $userSerializerForHydration;

        self::validateHydrationStrategy($clone);

        return $clone;
    }

    /**
     * @internal
     */
    public function serializationContext(): array
    {
        return $this->serializationContext;
    }

    public function withSerializationContext(array $serializationContext): self
    {
        $clone = clone $this;
        $clone->serializationContext = $serializationContext;

        self::validateHydrationStrategy($clone);

        return $clone;
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

    public function withFieldName(?string $fieldName): self
    {
        $clone = clone $this;
        $clone->fieldName = $fieldName;

        return $clone;
    }

    public function format(): ?string
    {
        return $this->format;
    }

    public function withFormat(?string $format): self
    {
        $clone = clone $this;
        $clone->format = $format;

        return $clone;
    }

    public function acceptUpdatesFromParent(): bool
    {
        return $this->updateFromParent;
    }

    public function onUpdated(): string|array|null
    {
        return $this->onUpdated;
    }

    public function withOnUpdated(string|array|null $onUpdated): self
    {
        $clone = clone $this;
        $clone->onUpdated = $onUpdated;

        return $clone;
    }

    public function url(): UrlMapping|false
    {
        return $this->url;
    }

    public function withUrl(bool|UrlMapping $url): self
    {
        $clone = clone $this;
        $clone->url = (true === $url) ? new UrlMapping() : $url;

        return $clone;
    }

    public function modifier(): ?string
    {
        return $this->modifier;
    }

    private static function validateHydrationStrategy(self $liveProp): void
    {
        if ($liveProp->useSerializerForHydration && ($liveProp->hydrateWith || $liveProp->dehydrateWith)) {
            throw new \InvalidArgumentException('Cannot use useSerializerForHydration with hydrateWith or dehydrateWith.');
        }
    }
}
