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
use Symfony\UX\Image\UrlGenerator\ImgixUrlBuilder;

#[CoversClass(ImgixUrlBuilder::class)]
final class ImgixUrlBuilderTest extends TestCase
{
    private ImgixUrlBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ImgixUrlBuilder();
    }

    public function testGetProviderName()
    {
        $this->assertSame('imgix', ImgixUrlBuilder::getProviderName());
    }

    public function testBuildUrlWithWidthAndHeight()
    {
        $baseUrl = 'https://demo.imgix.net';
        $path = 'sample.jpg';
        $profileConfig = [];
        $variantConfig = [
            'width' => 800,
            'height' => 600,
            'mode' => 'fit',
            'quality' => 85,
        ];

        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);

        $this->assertStringContainsString('w=800', $url);
        $this->assertStringContainsString('h=600', $url);
        $this->assertStringContainsString('fit=scale', $url);
        $this->assertStringContainsString('q=85', $url);
        $this->assertStringContainsString('auto=format%2Ccompress', $url);
    }

    public function testBuildUrlWithDifferentModes()
    {
        $baseUrl = 'https://demo.imgix.net';
        $path = 'sample.jpg';
        $profileConfig = [];

        // Test crop mode
        $variantConfig = ['width' => 400, 'mode' => 'crop'];
        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);
        $this->assertStringContainsString('fit=crop', $url);

        // Test fill mode
        $variantConfig = ['width' => 400, 'mode' => 'fill'];
        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);
        $this->assertStringContainsString('fit=fillmax', $url);
    }

    public function testRejectsUnknownMode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder->buildUrl('https://demo.imgix.net', 'sample.jpg', [], ['mode' => 'unknown']);
    }

    public function testRejectsCredentialedBaseUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder->buildUrl('https://user:secret@demo.imgix.net', 'sample.jpg', [], []);
    }

    public function testBuildUrlWithMinimalConfig()
    {
        $baseUrl = 'https://demo.imgix.net';
        $path = 'sample.jpg';
        $profileConfig = [];
        $variantConfig = [];

        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);

        $this->assertSame('https://demo.imgix.net/sample.jpg?auto=format%2Ccompress', $url);
    }

    public function testBuildUrlWithExistingQueryParams()
    {
        $baseUrl = 'https://demo.imgix.net/folder?existing=param';
        $path = 'sample.jpg';
        $profileConfig = [];
        $variantConfig = ['width' => 400];

        $url = $this->builder->buildUrl($baseUrl, $path, $profileConfig, $variantConfig);

        $this->assertStringContainsString('existing=param', $url);
        $this->assertStringContainsString('&w=400', $url);
    }
}
