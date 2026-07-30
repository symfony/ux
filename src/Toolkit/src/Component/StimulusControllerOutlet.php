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
 * A Stimulus outlet declared by a controller's `static outlets` and documented with an `@outlet` tag.
 *
 * The name is read from the code; the description comes from the docblock. The `attribute` is the
 * `data-*-outlet` attribute the outlet is bound to, precomputed from the controller identifier and the name.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerOutlet
{
    public function __construct(
        public readonly string $name,
        public readonly string $attribute,
        public readonly string $description,
    ) {
    }
}
