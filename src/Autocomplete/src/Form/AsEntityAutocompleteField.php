<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Form;

/**
 * All form types that want to expose autocomplete functionality should have this.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsEntityAutocompleteField
{
    public function __construct(
        private ?string $alias = null,
        private string $route = 'ux_entity_autocomplete',
    ) {
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @internal
     *
     * @param class-string $class
     */
    public static function shortName(string $class): string
    {
        if ($pos = (int) strrpos($class, '\\')) {
            $class = substr($class, $pos + 1);
        }

        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class));
    }

    /**
     * @internal
     *
     * @param class-string $class
     */
    public static function getInstance(string $class): ?self
    {
        $reflectionClass = new \ReflectionClass($class);

        $attributes = $reflectionClass->getAttributes(self::class);

        if (0 === \count($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }
}
