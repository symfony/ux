<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
trait HasAttributesTrait
{
    #[LiveProp(hydrateWith: 'hydrateAttributes', dehydrateWith: 'dehydrateAttributes')]
    public ComponentAttributes $attributes;

    public function setAttributes(array $attributes): void
    {
        $this->attributes = new ComponentAttributes($attributes);
    }

    /**
     * This "catches" any extra props sent to `component()` and
     * makes them available as "attributes".
     *
     * @internal
     */
    #[PostMount(priority: -1000)]
    public function mountAttributes(array $data): array
    {
        if (isset($this->attributes)) {
            // attributes might already be set if a user used an "attributes" prop
            // when calling `component()`
            $data = array_merge($this->attributes->all(), $data);
        }

        foreach ($data as $key => $value) {
            if (!is_scalar($value) && null !== $value) {
                throw new \LogicException(sprintf('Unable to use "%s" (%s) as an attribute. Attributes must be scalar or null. If you meant to mount this value on your component, make sure this is a writable property.', $key, get_debug_type($value)));
            }
        }

        $this->attributes = new ComponentAttributes($data);

        return [];
    }

    /**
     * Required for dehydrating the attributes when used with live
     * components.
     *
     * @internal
     */
    public static function dehydrateAttributes(ComponentAttributes $attributes): array
    {
        return $attributes->all();
    }

    /**
     * This stub is required to prevent Symfony's normalizer system from
     * being used for $attributes when the live component is hydrated.
     *
     * @internal
     */
    public static function hydrateAttributes(array $attributes): array
    {
        // todo, is there a more elegant solution?
        return $attributes;
    }
}
