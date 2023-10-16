<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Util;

use function Symfony\Component\String\u;

class FilenameHelper
{
    public static function getElementId(string $filename): string
    {
        return u($filename)
            ->afterLast('/')
            ->replace('.', '-')
            ->lower()
            ->toString();
    }
}
