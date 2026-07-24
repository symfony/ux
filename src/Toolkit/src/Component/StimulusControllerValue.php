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
 * A Stimulus value declared by a controller's `static values` and documented with an `@value` tag.
 *
 * The name and type are read from the code; the description comes from the docblock. The
 * `attribute` is the `data-*` attribute Stimulus binds the value to, precomputed from the
 * controller identifier and the value name.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerValue
{
    public function __construct(
        public readonly string $name,
        public readonly string $attribute,
        public readonly string $type,
        public readonly ?string $default,
        public readonly string $description,
    ) {
    }
}
