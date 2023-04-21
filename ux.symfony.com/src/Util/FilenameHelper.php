<?php

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
