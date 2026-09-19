<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Twig\RenderOptionsFactory;

final class RenderOptionsFactoryTest extends TestCase
{
    public function testItConvertsTheLayoutAndFitStringsToEnums()
    {
        $options = RenderOptionsFactory::create(layout: 'fixed', width: 400, fit: 'contain');

        self::assertSame(Layout::Fixed, $options->layout);
        self::assertSame(Fit::Contain, $options->fit);
    }

    public function testFitStaysNullWhenNotProvided()
    {
        $options = RenderOptionsFactory::create(width: 400);

        self::assertNull($options->fit);
    }

    public function testItCarriesAnExplicitFormat()
    {
        self::assertSame('webp', RenderOptionsFactory::create(width: 400, format: 'webp')->format);
    }

    public function testAnUnknownOptionKeyFailsClearly()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image option "class": expected one of "layout", "width", "height", "fit", "format", "quality", "priority", "objectFit", "breakpoints", "operations".');

        RenderOptionsFactory::createFromArray(['width' => 400, 'class' => 'rounded']);
    }

    public function testAnInvalidLayoutFailsClearly()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "layout" value "not-a-layout": expected one of "fixed", "constrained", "full-width".');

        RenderOptionsFactory::create(layout: 'not-a-layout', width: 400);
    }

    public function testAnInvalidFitFailsClearly()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "fit" value "not-a-fit": expected one of "cover", "contain", "scale-down".');

        RenderOptionsFactory::create(width: 400, fit: 'not-a-fit');
    }
}
