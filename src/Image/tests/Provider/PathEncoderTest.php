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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Provider\PathEncoder;

final class PathEncoderTest extends TestCase
{
    #[DataProvider('providePaths')]
    public function testEncode(string $input, string $expected)
    {
        $this->assertSame($expected, PathEncoder::encode($input));
    }

    public static function providePaths(): iterable
    {
        yield 'plain path' => ['hero.jpg', 'hero.jpg'];
        yield 'path with subdirectory' => ['images/hero.jpg', 'images/hero.jpg'];
        yield 'path with space' => ['hero image.jpg', 'hero%20image.jpg'];
        yield 'path segment with question mark' => ['a?b=1/hero.jpg', 'a%3Fb%3D1/hero.jpg'];
        yield 'leading slash is stripped' => ['/hero.jpg', 'hero.jpg'];
        yield 'multiple leading slashes' => ['///hero.jpg', 'hero.jpg'];
        yield 'leading slash with space' => ['/hero image.jpg', 'hero%20image.jpg'];
    }
}
