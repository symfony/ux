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

use PHPUnit\Framework\TestCase;
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
}
