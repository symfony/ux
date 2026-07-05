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
 * A prop declared in a Twig component's `{%- props ... -%}` tag.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class DeclaredProp
{
    public function __construct(
        public readonly string $name,
        public readonly bool $hasDefault,
        /**
         * The default value rendered in its canonical form: strings are single-quoted
         * (`'default'`, `''`), while booleans, numbers, `null`, arrays and hashes stay bare.
         * Null when the prop has no default (i.e. it is required).
         */
        public readonly ?string $default = null,
    ) {
    }
}
