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
 * A Stimulus CSS class declared by a controller's `static classes` and documented with a `@css-class` tag.
 *
 * The name is read from the code; the description comes from the docblock. The `attribute` is the
 * `data-*-class` attribute the class is bound to, precomputed from the controller identifier and the name.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerClass
{
    public function __construct(
        public readonly string $name,
        public readonly string $attribute,
        public readonly string $description,
    ) {
    }
}
