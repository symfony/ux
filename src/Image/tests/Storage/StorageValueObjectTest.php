<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Storage\StorageName;
use Symfony\UX\Image\Storage\StoragePath;

#[CoversClass(StorageName::class)]
#[CoversClass(StoragePath::class)]
final class StorageValueObjectTest extends TestCase
{
    public function testCanonicalValuesAreStringable()
    {
        $name = new StorageName('product.images');
        $path = StoragePath::fromAssetPath('/products/photo.jpg');

        self::assertSame('product.images', (string) $name);
        self::assertSame('products/photo.jpg', $path->getValue());
        self::assertSame('products/photo.jpg', (string) $path);
    }

    #[DataProvider('invalidPaths')]
    public function testRejectsUnsafePaths(string $path)
    {
        $this->expectException(\InvalidArgumentException::class);

        new StoragePath($path);
    }

    public static function invalidPaths(): iterable
    {
        yield 'url' => ['https://example.com/photo.jpg'];
        yield 'empty' => ['/'];
        yield 'empty segment' => ['images//photo.jpg'];
        yield 'dot segment' => ['images/./photo.jpg'];
        yield 'parent segment' => ['images/../photo.jpg'];
        yield 'backslash' => ['images\\photo.jpg'];
        yield 'nul' => ["images\0photo.jpg"];
    }

    public function testRejectsUnsafeStorageName()
    {
        $this->expectException(\InvalidArgumentException::class);

        new StorageName('../private');
    }
}
