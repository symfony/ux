<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\UrlGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\UrlGenerator\CloudinaryUrlBuilder;

#[CoversClass(CloudinaryUrlBuilder::class)]
final class CloudinaryUrlBuilderTest extends TestCase
{
    private CloudinaryUrlBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new CloudinaryUrlBuilder();
    }

    public function testGetProviderName()
    {
        $this->assertSame('cloudinary', CloudinaryUrlBuilder::getProviderName());
    }

    public function testBuildUrlWithWidthAndHeight()
    {
        $baseUrl = 'https://res.cloudinary.com/demo/image/upload/';
        $path = 'sample.jpg';
        $profileConfig = [];
        $variantConfig = [
            'width' => 800,
            'height' => 600,
            'mode' => 'fit',
        ];

        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);

        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/w_800,h_600,c_fit,f_auto,q_auto/sample.jpg',
            $url
        );
    }

    public function testBuildUrlWithDifferentModes()
    {
        $baseUrl = 'https://res.cloudinary.com/demo/image/upload/';
        $path = 'sample.jpg';
        $profileConfig = [];

        // Test crop mode
        $variantConfig = ['width' => 400, 'mode' => 'crop'];
        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);
        $this->assertStringContainsString('c_crop', $url);

        // Test fill mode
        $variantConfig = ['width' => 400, 'mode' => 'fill'];
        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);
        $this->assertStringContainsString('c_fill', $url);
    }

    public function testRejectsUnknownMode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder->buildUrl('https://res.cloudinary.com/demo', 'sample.jpg', [], ['mode' => 'unknown']);
    }

    public function testEncodesPathSegments()
    {
        self::assertStringEndsWith('/folder/my%20photo.jpg', $this->builder->buildUrl('https://res.cloudinary.com/demo', 'folder/my photo.jpg', [], []));
    }

    public function testBuildUrlWithMinimalConfig()
    {
        $baseUrl = 'https://res.cloudinary.com/demo/image/upload/';
        $path = 'sample.jpg';
        $profileConfig = [];
        $variantConfig = [];

        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);

        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/f_auto,q_auto/sample.jpg',
            $url
        );
    }

    public function testBuildUrlPreservesAdvertisedVariantFormat()
    {
        $url = $this->builder->buildUrl(
            'https://res.cloudinary.com/demo/image/upload/',
            'sample.jpg',
            [],
            ['format' => 'webp'],
        );

        self::assertSame(
            'https://res.cloudinary.com/demo/image/upload/f_webp,q_auto/sample.jpg',
            $url,
        );
    }

    public function testRejectsUnsafeFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported Cloudinary image format');

        $this->builder->buildUrl('https://res.cloudinary.com/demo', 'sample.jpg', [], ['format' => 'webp,q_1']);
    }

    public function testNormalizesPathSlashes()
    {
        self::assertStringEndsWith('/sample.jpg', $this->builder->buildUrl('https://res.cloudinary.com/demo', '/sample.jpg/', [], []));
    }
}
