<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class Point
{
    public float $x;
    public float $y;

    public static function create(float $x, float $y): self
    {
        $point = new self();
        $point->x = $x;
        $point->y = $y;

        return $point;
    }
}
