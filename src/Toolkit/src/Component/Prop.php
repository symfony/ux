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

use Symfony\UX\Toolkit\Assert;

/**
 * A prop declared in a Twig component's docblock (`{# @prop name type description #}`).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Prop
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $description,
        /**
         * The default value sourced from the `{%- props -%}` declaration, rendered in its
         * canonical form, or null when the prop is required (no default declared).
         */
        public readonly ?string $default = null,
    ) {
        Assert::propName($this->name);
    }
}
