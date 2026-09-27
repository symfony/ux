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

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\UX\Image\Bridge\Glide\SymfonyResponseFactory;

final class SymfonyResponseFactoryTest extends TestCase
{
    private string $cache;

    private string $timezone;

    protected function setUp(): void
    {
        $this->cache = sys_get_temp_dir().'/ux_image_glide_response_'.bin2hex(random_bytes(4));
        mkdir($this->cache, recursive: true);
        file_put_contents($this->cache.'/hero.jpg', 'fake image bytes');

        $this->timezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
        unlink($this->cache.'/hero.jpg');
        rmdir($this->cache);
    }

    public function testExpiresIsAGmtDateWhateverThePhpTimezone(): void
    {
        date_default_timezone_set('Pacific/Kiritimati');

        $response = new SymfonyResponseFactory($this->cache)->create(new Filesystem(new LocalFilesystemAdapter($this->cache)), 'hero.jpg');

        $expires = \DateTimeImmutable::createFromFormat('D, d M Y H:i:s \G\M\T', $response->headers->get('Expires'), new \DateTimeZone('UTC'));
        self::assertEqualsWithDelta(time() + 31536000, $expires->getTimestamp(), 5);
    }

    public function testALocalCacheHitIsServedAsAFileWithValidators(): void
    {
        $response = new SymfonyResponseFactory($this->cache)->create(new Filesystem(new LocalFilesystemAdapter($this->cache)), 'hero.jpg');

        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertSame($this->cache.'/hero.jpg', $response->getFile()->getPathname());
        self::assertNotNull($response->getEtag());
        self::assertNotNull($response->getLastModified());
    }

    public function testTheCachePolicyComesFromTheFactory(): void
    {
        $response = new SymfonyResponseFactory($this->cache, 3600, false)->create(new Filesystem(new LocalFilesystemAdapter($this->cache)), 'hero.jpg');

        self::assertSame(3600, $response->getMaxAge());
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
        self::assertFalse($response->headers->hasCacheControlDirective('public'));
    }
}
