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
use Symfony\UX\Image\Provider\PassThroughProvider;
use Symfony\UX\Image\Provider\UrlPatternProvider;

class PassThroughProviderTest extends TestCase
{
    public function testReturnsOriginalPath(): void
    {
        $provider = new PassThroughProvider();
        $this->assertEquals('/images/hero.jpg', $provider->getImage('/images/hero.jpg', []));
    }

    public function testPrependsAssetsPath(): void
    {
        $provider = new PassThroughProvider();
        $provider->configure(['assets_path' => '/assets']);
        $this->assertEquals('/assets/hero.jpg', $provider->getImage('hero.jpg', []));
    }

    public function testGetName(): void
    {
        $provider = new PassThroughProvider();
        $this->assertEquals('passthrough', $provider->getName());
    }
}

class UrlPatternProviderTest extends TestCase
{
    public function testGeneratesUrlFromPattern(): void
    {
        $provider = new UrlPatternProvider();
        $provider->configure(['pattern' => '/img/{src}?w={width}&f={format}']);

        $url = $provider->getImage('/images/hero.jpg', ['width' => 800, 'format' => 'webp']);
        $this->assertEquals('/img/images/hero.jpg?w=800&f=webp', $url);
    }

    public function testRequiresPattern(): void
    {
        $provider = new UrlPatternProvider();
        $this->expectException(\InvalidArgumentException::class);
        $provider->configure([]);
    }

    public function testGetName(): void
    {
        $provider = new UrlPatternProvider();
        $this->assertEquals('url_pattern', $provider->getName());
    }
}
