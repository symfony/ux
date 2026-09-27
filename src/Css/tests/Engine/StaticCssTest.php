<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Engine\StaticCss;

final class StaticCssTest extends TestCase
{
    public function testTheWildcardOfAShorthandExpandsToTheValuesOfItsProperty(): void
    {
        $engine = Engine::fromProjectConfig(['theme' => ['tokens' => ['spacing' => ['sm' => ['value' => '0.5rem']]]]]);
        $staticCss = new StaticCss($engine);

        $styles = $staticCss->styles([['properties' => ['p' => ['*']]]]);

        $this->assertSame([['p' => 'sm'], ['p' => '-sm']], $styles);
    }
}
