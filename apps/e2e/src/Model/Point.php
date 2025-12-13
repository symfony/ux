<?php

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
