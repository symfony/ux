<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Exception;

/**
 * A style hash that css() refuses: unknown property or condition, value not allowed by the strictness options.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class InvalidStyleException extends InvalidArgumentException
{
}
