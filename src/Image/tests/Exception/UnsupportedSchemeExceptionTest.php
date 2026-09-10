<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\UnsupportedSchemeException;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\UXImageBundle;

final class UnsupportedSchemeExceptionTest extends TestCase
{
    private array $originalBridges;

    protected function setUp(): void
    {
        $this->originalBridges = UXImageBundle::$bridges;
    }

    protected function tearDown(): void
    {
        UXImageBundle::$bridges = $this->originalBridges;
    }

    public function testMessageWhenSchemeIsUnknown()
    {
        UXImageBundle::$bridges = [];

        $exception = new UnsupportedSchemeException(new Dsn('cloudflare://cdn.example.com'));

        self::assertSame('The image provider "cloudflare" is not supported.', $exception->getMessage());
    }

    public function testMessageWhenBridgeIsKnownButFactoryClassIsMissing()
    {
        UXImageBundle::$bridges = [
            'glide' => ['factory' => 'Symfony\UX\Image\Bridge\Glide\NotInstalledFactory'],
        ];

        $exception = new UnsupportedSchemeException(new Dsn('glide://default/images'));

        self::assertSame('Unable to generate images via "glide" as the bridge is not installed. Try running "composer require symfony/ux-glide-image".', $exception->getMessage());
    }
}
