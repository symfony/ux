<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\NullProvider;

final class NullProviderTest extends TestCase
{
    public function testItReturnsTheOriginalPathWhateverTheTransformation()
    {
        $transformation = new ImageTransformation('/uploads/hero.jpg', width: 800, height: 450, fit: Fit::Cover, format: 'webp', quality: 80);

        self::assertSame('/uploads/hero.jpg', new NullProvider()->generateUrl($transformation));
    }
}
