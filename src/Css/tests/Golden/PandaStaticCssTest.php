<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Golden;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Engine\StaticCss;

final class PandaStaticCssTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../Fixtures/Panda';

    public static function provideCases(): iterable
    {
        foreach (new PandaCaseLoader(self::FIXTURES)->cases() as $id => $case) {
            if ('static-css' === $case->kind && null === $case->unsupported) {
                yield $id => [$case];
            }
        }
    }

    #[DataProvider('provideCases')]
    public function testStyleObjectsMatchPanda(PandaCase $case): void
    {
        $loader = new PandaCaseLoader(self::FIXTURES);
        $staticCss = new StaticCss(new Engine($loader->configFor($case)));

        $styles = $staticCss->styles($case->inputs[0]['css']);

        $this->assertSame($case->expected['styles'], $styles);
    }
}
