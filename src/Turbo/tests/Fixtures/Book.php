<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Fixtures;

/**
 * Minimal fixture used by Turbo PHPUnit tests that exercise the
 * `turbo_stream_listen` Twig function with an entity instance.
 *
 * @internal
 */
final class Book
{
    public int $id;
}
