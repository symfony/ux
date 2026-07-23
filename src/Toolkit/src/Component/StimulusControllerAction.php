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
 * A Stimulus action method documented with an `@action` tag.
 *
 * Actions are opt-in: only methods flagged with `@action` are treated as public API, so internal
 * (but still public) helper methods stay out of the reference.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerAction
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {
    }
}
