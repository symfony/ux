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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Renderer\ImageRenderOptions;

#[CoversClass(ImageRenderOptions::class)]
final class ImageRenderOptionsTest extends TestCase
{
    public function testExposesNormalizedOptions()
    {
        $options = new ImageRenderOptions(
            sizes: '50vw',
            alt: 'Product',
            lazy: false,
            fetchPriority: 'high',
            class: 'hero',
            decoding: 'sync',
            variant: 'large',
            srcset: [2 => '/small.jpg 400w', 7 => '/large.jpg 800w'],
            attributes: ['data-role' => 'hero'],
        );

        self::assertSame('50vw', $options->getSizes());
        self::assertSame('Product', $options->getAlt());
        self::assertFalse($options->isLazy());
        self::assertSame('high', $options->getFetchPriority());
        self::assertSame('hero', $options->getClass());
        self::assertSame('sync', $options->getDecoding());
        self::assertSame('large', $options->getVariant());
        self::assertSame(['/small.jpg 400w', '/large.jpg 800w'], $options->getSrcset());
        self::assertSame(['data-role' => 'hero'], $options->getAttributes());
    }

    #[DataProvider('invalidOptions')]
    public function testRejectsInvalidOptions(array $arguments, string $message)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new ImageRenderOptions(...$arguments);
    }

    public static function invalidOptions(): iterable
    {
        yield 'fetch priority' => [['fetchPriority' => 'urgent'], 'fetch priority'];
        yield 'decoding' => [['decoding' => 'later'], 'decoding hint'];
        yield 'empty variant' => [['variant' => '  '], 'variant must not be empty'];
        yield 'non-string srcset' => [['srcset' => [42]], 'srcset entries must be strings'];
        yield 'empty srcset' => [['srcset' => ['  ']], 'srcset entries must not be empty'];
        yield 'invalid attribute name' => [['attributes' => ['bad name' => 'value']], 'Unsafe image attribute name'];
        yield 'event attribute' => [['attributes' => ['ONCLICK' => 'value']], 'Unsafe image attribute name'];
        yield 'managed attribute' => [['attributes' => ['src' => '/other.jpg']], 'managed by ImageRenderOptions'];
        yield 'managed sizes attribute' => [['attributes' => ['sizes' => '50vw']], 'managed by ImageRenderOptions'];
        yield 'managed alt attribute' => [['attributes' => ['alt' => 'Photo']], 'managed by ImageRenderOptions'];
        yield 'managed class attribute' => [['attributes' => ['class' => 'hero']], 'managed by ImageRenderOptions'];
        yield 'non-scalar attribute' => [['attributes' => ['data-value' => []]], 'must be scalar or null'];
    }
}
