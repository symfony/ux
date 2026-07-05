<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Component;

/**
 * The set of props declared in a Twig component's `{%- props ... -%}` tag.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PropsDeclaration
{
    /**
     * @param list<DeclaredProp> $props
     */
    public function __construct(
        public readonly array $props,
    ) {
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(static fn (DeclaredProp $prop) => $prop->name, $this->props);
    }

    public function get(string $name): ?DeclaredProp
    {
        foreach ($this->props as $prop) {
            if ($prop->name === $name) {
                return $prop;
            }
        }

        return null;
    }
}
