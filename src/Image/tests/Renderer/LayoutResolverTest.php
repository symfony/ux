<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Renderer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Renderer\LayoutResolver;

final class LayoutResolverTest extends TestCase
{
    public function testFixedGivesOneAndTwoTimesTheWidth()
    {
        self::assertSame([400, 800], new LayoutResolver()->breakpoints(Layout::Fixed, 400));
    }

    public function testConstrainedAddsTheLadderEntriesBelowTwiceTheWidth()
    {
        self::assertSame(
            [640, 750, 800, 828, 960, 1080, 1280, 1600],
            new LayoutResolver()->breakpoints(Layout::Constrained, 800),
        );
    }

    public function testFullWidthGivesTheWholeLadderAscending()
    {
        $breakpoints = new LayoutResolver()->breakpoints(Layout::FullWidth, null);

        self::assertSame(640, $breakpoints[0]);
        self::assertSame(6016, $breakpoints[\count($breakpoints) - 1]);
        self::assertCount(\count(LayoutResolver::DEFAULT_RESOLUTIONS), $breakpoints);
    }

    public function testConstrainedWithoutAWidthGivesNothing()
    {
        self::assertSame([], new LayoutResolver()->breakpoints(Layout::Constrained, null));
    }

    #[DataProvider('provideSizes')]
    public function testSizes(Layout $layout, ?int $width, ?string $expected)
    {
        self::assertSame($expected, new LayoutResolver()->sizes($layout, $width));
    }

    public static function provideSizes(): iterable
    {
        yield 'fixed' => [Layout::Fixed, 400, '400px'];
        yield 'constrained' => [Layout::Constrained, 800, '(min-width: 800px) 800px, 100vw'];
        yield 'full width' => [Layout::FullWidth, null, '100vw'];
        yield 'constrained without width' => [Layout::Constrained, null, null];
    }

    public function testFixedStyle()
    {
        self::assertSame(
            ['object-fit' => 'cover', 'width' => '120px', 'height' => '40px'],
            new LayoutResolver()->style(Layout::Fixed, 120, 40),
        );
    }

    public function testConstrainedStyle()
    {
        self::assertSame(
            [
                'object-fit' => 'cover',
                'max-width' => '800px',
                'max-height' => '450px',
                'aspect-ratio' => '800 / 450',
                'width' => '100%',
                'height' => 'auto',
            ],
            new LayoutResolver()->style(Layout::Constrained, 800, 450),
        );
    }

    public function testFullWidthStyleWithADerivableRatioEmitsTheRatioAndAnAutoHeight()
    {
        self::assertSame(
            ['object-fit' => 'cover', 'width' => '100%', 'aspect-ratio' => '800 / 450', 'height' => 'auto'],
            new LayoutResolver()->style(Layout::FullWidth, 800, 450),
        );
    }

    public function testFullWidthStyleWithOnlyAHeightEmitsTheHeightAndNoRatio()
    {
        self::assertSame(
            ['object-fit' => 'cover', 'width' => '100%', 'height' => '450px'],
            new LayoutResolver()->style(Layout::FullWidth, null, 450),
        );
    }

    public function testStyleOmitsAspectRatioWhenADimensionIsMissing()
    {
        self::assertArrayNotHasKey('aspect-ratio', new LayoutResolver()->style(Layout::Constrained, 800, null));
    }

    #[DataProvider('provideLayoutsWithARatio')]
    public function testAnyStyleWithAnAspectRatioAlsoDeclaresAnAutoHeight(Layout $layout)
    {
        $style = new LayoutResolver()->style($layout, 800, 450);

        self::assertArrayHasKey('aspect-ratio', $style);
        self::assertSame('auto', $style['height'] ?? null);
    }

    public static function provideLayoutsWithARatio(): iterable
    {
        yield 'constrained' => [Layout::Constrained];
        yield 'full width' => [Layout::FullWidth];
    }

    public function testObjectFitIsOverridable()
    {
        self::assertSame('contain', new LayoutResolver()->style(Layout::Fixed, 120, 40, 'contain')['object-fit']);
    }
}
