<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Metadata;

use Symfony\Component\PropertyInfo\Type;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LivePropMetadata
{
    public function __construct(
        private string $name,
        private LiveProp $liveProp,
        private ?string $typeName,
        private bool $isBuiltIn,
        private bool $allowsNull,
        private ?Type $collectionValueType,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->typeName;
    }

    public function isBuiltIn(): bool
    {
        return $this->isBuiltIn;
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    public function urlMapping(): ?UrlMapping
    {
        return $this->liveProp->url() ?: null;
    }

    public function calculateFieldName(object $component, string $fallback): string
    {
        return $this->liveProp->calculateFieldName($component, $fallback);
    }

    /**
     * @return array<string>
     */
    public function writablePaths(): array
    {
        return $this->liveProp->writablePaths();
    }

    public function hydrateMethod(): ?string
    {
        return $this->liveProp->hydrateMethod();
    }

    public function dehydrateMethod(): ?string
    {
        return $this->liveProp->dehydrateMethod();
    }

    public function isIdentityWritable(): bool
    {
        return $this->liveProp->isIdentityWritable();
    }

    public function acceptUpdatesFromParent(): bool
    {
        return $this->liveProp->acceptUpdatesFromParent();
    }

    public function useSerializerForHydration(): bool
    {
        return $this->liveProp->useSerializerForHydration();
    }

    public function serializationContext(): array
    {
        return $this->liveProp->serializationContext();
    }

    public function collectionValueType(): ?Type
    {
        return $this->collectionValueType;
    }

    public function getFormat(): ?string
    {
        return $this->liveProp->format();
    }

    public function onUpdated(): string|array|null
    {
        return $this->liveProp->onUpdated();
    }

    public function hasModifier(): bool
    {
        return null !== $this->liveProp->modifier();
    }

    /**
     * Applies a modifier specified in LiveProp attribute.
     *
     * If a modifier is specified, a modified clone is returned.
     * Otherwise, the metadata is returned as it is.
     */
    public function withModifier(object $component): self
    {
        if (null === ($modifier = $this->liveProp->modifier())) {
            return $this;
        }

        if (!method_exists($component, $modifier)) {
            throw new \LogicException(\sprintf('Method "%s::%s()" given in LiveProp "modifier" does not exist.', $component::class, $modifier));
        }

        $modifiedLiveProp = $component->{$modifier}($this->liveProp);
        if (!$modifiedLiveProp instanceof LiveProp) {
            throw new \LogicException(\sprintf('Method "%s::%s()" should return an instance of "%s" (given: "%s").', $component::class, $modifier, LiveProp::class, get_debug_type($modifiedLiveProp)));
        }

        $clone = clone $this;
        $clone->liveProp = $modifiedLiveProp;

        return $clone;
    }
}
