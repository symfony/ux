<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): iterable
    {
        yield new TwigFunction('print_r', function (mixed $value): string {
            return '<pre>'.print_r($value, true).'</pre>';
        }, ['is_safe' => ['html']]);
    }
}