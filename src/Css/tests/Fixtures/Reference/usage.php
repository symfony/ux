<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Fixtures\Reference;

use Symfony\UX\Css\Twig\CssRuntime;

function render(CssRuntime $runtime): void
{
    echo $runtime->css(['color' => 'primary', 'p' => 'md', '_hover' => ['bg' => 'blue.500'], 'md' => ['p' => 'sm']]);
    echo $runtime->css(['color' => 'anything', 'display' => 'flex', 'fontSize' => '2xl']);
    echo $runtime->css(['zIndex' => 10, 'opacity' => 0.5, 'p' => ['sm', 'md'], 'color' => ['base' => 'primary']]);
    echo $runtime->css(['_hover' => ['_focus' => ['color' => ['deeper' => 'than the typed level']]]]);
    echo $runtime->css(null);
    echo $runtime->css(['color' => ['base' => 'primary', '_hover' => ['base' => 'blue.500', 'md' => 'primary']]]);
    echo $runtime->css(['p' => ['base' => ['md', 'sm']]]);
    echo $runtime->css(['color' => ['_hover' => ['_focus' => 'primary']]]);
    echo $runtime->css(['@/md' => ['p' => 'sm', '_focus' => ['color' => 'primary']]]);
    echo $runtime->css(['_hover' => ['color' => new \stdClass()]]); // error
    echo $runtime->css(['color' => new \stdClass()]); // error
    echo $runtime->css(['color' => ['base' => new \stdClass()]]); // error
}

function renderCva(CssRuntime $runtime): void
{
    $variants = ['size' => ['sm' => ['p' => 'sm'], 'md' => ['p' => 'md', '_hover' => ['color' => 'primary']]]];
    $compound = [['size' => 'md', 'css' => ['color' => 'primary']]];
    echo $runtime->cva(base: ['color' => 'primary'], variants: $variants, compoundVariants: $compound)->apply();
    echo $runtime->cva(variants: ['level' => ['one' => ['p' => 'sm']]], defaultVariants: ['level' => 'one'])->apply();
    echo $runtime->cva(variants: ['level' => [1 => ['p' => 'sm']]], defaultVariants: ['level' => 1])->apply();
    echo $runtime->cva(base: ['color' => new \stdClass()])->apply(); // error
    echo $runtime->cva(variants: ['size' => ['sm' => ['color' => new \stdClass()]]])->apply(); // error
    echo $runtime->cva(defaultVariants: ['size' => new \stdClass()])->apply(); // error
}
