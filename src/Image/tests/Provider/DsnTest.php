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
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Provider\Dsn;

final class DsnTest extends TestCase
{
    public function testItParsesAHostOnlyDsn()
    {
        $dsn = new Dsn('cloudflare://cdn.example.com');

        self::assertSame('cloudflare', $dsn->getScheme());
        self::assertSame('cdn.example.com', $dsn->getHost());
        self::assertNull($dsn->getPath());
    }

    public function testItParsesADsnWithAPlaceholderHostAPathAndOptions()
    {
        $dsn = new Dsn('glide://default/images?source=/app/public/uploads&sign_key=s3cret');

        self::assertSame('glide', $dsn->getScheme());
        self::assertSame('default', $dsn->getHost());
        self::assertSame('/images', $dsn->getPath());
        self::assertSame('/app/public/uploads', $dsn->getOption('source'));
        self::assertSame('s3cret', $dsn->getOption('sign_key'));
        self::assertNull($dsn->getOption('cache'));
        self::assertSame('fallback', $dsn->getOption('cache', 'fallback'));
    }

    public function testItRejectsADsnWithoutAScheme()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image provider DSN must contain a scheme.');

        new Dsn('cdn.example.com');
    }

    public function testItKeepsTheOriginalDsn()
    {
        self::assertSame('keycdn://zone.kxcdn.com', new Dsn('keycdn://zone.kxcdn.com')->getOriginalDsn());
    }

    public function testItParsesASchemeOnlyDsnWithoutAHost()
    {
        $dsn = new Dsn('cloudflare:');

        self::assertSame('cloudflare', $dsn->getScheme());
        self::assertNull($dsn->getHost());
        self::assertNull($dsn->getPath());
    }

    public function testItRejectsAnEmptyAuthorityDsn()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image provider DSN is invalid.');

        new Dsn('glide:///images');
    }
}
