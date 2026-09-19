<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide\Tests;

use League\Glide\Api\Api;
use League\Glide\Manipulators\Size;
use League\Glide\Server;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Image\Bridge\Glide\Controller\GlideController;
use Symfony\UX\Image\Bridge\Glide\ServerFactory;
use Symfony\UX\Image\Exception\InvalidArgumentException;

final class ServerFactoryTest extends TestCase
{
    private string $source;
    private string $cache;

    protected function setUp(): void
    {
        $this->source = sys_get_temp_dir().'/ux_image_glide_server_factory_'.bin2hex(random_bytes(8));
        $this->cache = $this->source.'/cache';
        mkdir($this->source, recursive: true);
        mkdir($this->cache, recursive: true);

        $image = imagecreatetruecolor(20, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, 0, 128, 0));
        imagejpeg($image, $this->source.'/hero.jpg', 90);
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->source);
    }

    public function testAnUnconfiguredDsnStillCapsTheOutputSize()
    {
        self::assertSame(ServerFactory::DEFAULT_MAX_IMAGE_SIZE, $this->maxImageSizeOf($this->server()));
    }

    public function testTheDsnCanRaiseOrLowerTheCap()
    {
        self::assertSame(10_000, $this->maxImageSizeOf($this->server('&max_image_size=10000')));
    }

    public function testAnOversizedRequestIsScaledDownToTheCap()
    {
        $response = new GlideController($this->server('&max_image_size=10000'))(Request::create('/images/hero.jpg?w=2000&h=2000'), 'hero.jpg');

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        self::assertSame([100, 100], \array_slice(getimagesizefromstring($body), 0, 2));
    }

    public function testANonNumericCapIsRejected()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Glide "max_image_size" DSN option must be a positive number of output pixels, "lots" given.');

        $this->server('&max_image_size=lots');
    }

    public function testAZeroCapIsRejectedRatherThanMeaningUnlimited()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Glide "max_image_size" DSN option must be a positive number of output pixels, "0" given.');

        $this->server('&max_image_size=0');
    }

    private function server(string $extraOptions = ''): Server
    {
        return ServerFactory::createFromDsn(\sprintf('glide://default/images?source=%s&cache=%s%s', $this->source, $this->cache, $extraOptions));
    }

    private function maxImageSizeOf(Server $server): ?int
    {
        $api = $server->getApi();
        self::assertInstanceOf(Api::class, $api);

        foreach ($api->getManipulators() as $manipulator) {
            if ($manipulator instanceof Size) {
                return $manipulator->getMaxImageSize();
            }
        }

        self::fail('The Glide server has no Size manipulator.');
    }
}
