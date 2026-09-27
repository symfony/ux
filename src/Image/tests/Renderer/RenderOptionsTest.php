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
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Renderer\RenderOptions;

final class RenderOptionsTest extends TestCase
{
    public function testBothDimensionsDefaultFitToCover()
    {
        $options = new RenderOptions(width: 800, height: 450);

        self::assertSame(Fit::Cover, $options->fit);
    }

    public function testOnlyAWidthLeavesFitNull()
    {
        $options = new RenderOptions(layout: Layout::Fixed, width: 800);

        self::assertNull($options->fit);
    }

    public function testOnlyAHeightLeavesFitNull()
    {
        $options = new RenderOptions(layout: Layout::FullWidth, height: 450);

        self::assertNull($options->fit);
    }

    public function testAnExplicitFitIsNeverOverridden()
    {
        $options = new RenderOptions(width: 800, height: 450, fit: Fit::Contain);

        self::assertSame(Fit::Contain, $options->fit);
    }

    #[DataProvider('provideInvalidDimensions')]
    public function testANonPositiveDimensionIsRejectedUpfront(?int $width, ?int $height, string $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new RenderOptions(width: $width, height: $height);
    }

    public static function provideInvalidDimensions(): iterable
    {
        yield 'zero width' => [0, 300, 'The "width" option must be a positive integer, 0 given.'];
        yield 'negative width' => [-1, 300, 'The "width" option must be a positive integer, -1 given.'];
        yield 'zero height' => [400, 0, 'The "height" option must be a positive integer, 0 given.'];
    }

    #[DataProvider('provideInvalidBreakpoints')]
    public function testANonPositiveBreakpointIsRejectedUpfront(array $breakpoints, string $message)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new RenderOptions(width: 400, breakpoints: $breakpoints);
    }

    public static function provideInvalidBreakpoints(): iterable
    {
        yield 'zero' => [[400, 0], 'The "breakpoints" option must only contain positive integers, 0 given.'];
        yield 'negative' => [[-5], 'The "breakpoints" option must only contain positive integers, -5 given.'];
        yield 'not an integer' => [['800'], 'The "breakpoints" option must only contain positive integers, "800" given.'];
    }

    public function testBreakpointsAreDeduplicatedAndSorted()
    {
        $options = new RenderOptions(width: 400, breakpoints: [800, 400, 800]);

        self::assertSame([400, 800], $options->breakpoints);
    }
}
