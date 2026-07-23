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
 * A Stimulus target declared by a controller's `static targets` and documented with a `@target` tag.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerTarget
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {
    }
}
