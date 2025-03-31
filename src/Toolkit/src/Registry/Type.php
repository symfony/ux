<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

/**
 * @internal
 *
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum Type: string
{
    case Local = 'local';
    case GitHub = 'github';
}
