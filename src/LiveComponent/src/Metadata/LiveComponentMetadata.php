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

use Symfony\UX\TwigComponent\ComponentMetadata;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class LiveComponentMetadata
{
    public function __construct(
        private ComponentMetadata $componentMetadata,
        /** @var list<LivePropMetadata|LegacyLivePropMetadata> */
        private array $livePropsMetadata,
    ) {
        uasort(
            $this->livePropsMetadata,
            static fn (LivePropMetadata|LegacyLivePropMetadata $a, LivePropMetadata|LegacyLivePropMetadata $b) => $a->hasModifier() <=> $b->hasModifier()
        );
    }

    public function getComponentMetadata(): ComponentMetadata
    {
        return $this->componentMetadata;
    }

    /**
     * @return list<LivePropMetadata|LegacyLivePropMetadata>
     */
    public function getAllLivePropsMetadata(object $component): iterable
    {
        foreach ($this->livePropsMetadata as $livePropMetadata) {
            yield $livePropMetadata->withModifier($component);
        }
    }

    /**
     * Looks at an array of "input prop" values and sees which of these correspond
     * with LiveProps that accept updates from the parent.
     *
     * Returns the final array of "input props" that should be used to update
     * LiveProps on the component.
     */
    public function getOnlyPropsThatAcceptUpdatesFromParent(array $inputProps): array
    {
        $writableProps = array_filter($this->livePropsMetadata, function (LivePropMetadata|LegacyLivePropMetadata $livePropMetadata) {
            return $livePropMetadata->acceptUpdatesFromParent();
        });

        $propNames = array_map(function ($livePropMetadata) {
            return $livePropMetadata->getName();
        }, $writableProps);

        return array_intersect_key($inputProps, array_flip($propNames));
    }

    /**
     * @return UrlMapping[]
     */
    public function getAllUrlMappings(): iterable
    {
        $urlMappings = [];
        foreach ($this->livePropsMetadata as $livePropMetadata) {
            if ($livePropMetadata->urlMapping()) {
                $urlMappings[$livePropMetadata->getName()] = $livePropMetadata->urlMapping();
            }
        }

        return $urlMappings;
    }

    public function hasQueryStringBindings($component): bool
    {
        foreach ($this->getAllLivePropsMetadata($component) as $livePropMetadata) {
            if ($livePropMetadata->urlMapping()) {
                return true;
            }
        }

        return false;
    }
}
