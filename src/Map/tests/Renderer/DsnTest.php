<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Exception\InvalidArgumentException;
use Symfony\UX\Map\Renderer\Dsn;

final class DsnTest extends TestCase
{
    /**
     * @dataProvider constructDsn
     */
    public function testConstruct(string $dsnString, string $scheme, string $host, ?string $user = null, array $options = [], ?string $path = null)
    {
        $dsn = new Dsn($dsnString);
        self::assertSame($dsnString, $dsn->getOriginalDsn());

        self::assertSame($scheme, $dsn->getScheme());
        self::assertSame($host, $dsn->getHost());
        self::assertSame($user, $dsn->getUser());
        self::assertSame($options, $dsn->getOptions());
    }

    public static function constructDsn(): iterable
    {
        yield 'simple dsn' => [
            'scheme://default',
            'scheme',
            'default',
        ];

        yield 'simple dsn including @ sign, but no user/password/token' => [
            'scheme://@default',
            'scheme',
            'default',
        ];

        yield 'simple dsn including : sign and @ sign, but no user/password/token' => [
            'scheme://:@default',
            'scheme',
            'default',
        ];

        yield 'simple dsn including user, : sign and @ sign, but no password' => [
            'scheme://user1:@default',
            'scheme',
            'default',
            'user1',
        ];

        yield 'dsn with user' => [
            'scheme://u$er@default',
            'scheme',
            'default',
            'u$er',
        ];

        yield 'dsn with user, and custom option' => [
            'scheme://u$er@default?api_key=MY_API_KEY',
            'scheme',
            'default',
            'u$er',
            [
                'api_key' => 'MY_API_KEY',
            ],
            '/channel',
        ];
    }

    /**
     * @dataProvider  invalidDsn
     */
    public function testInvalidDsn(string $dsnString, string $exceptionMessage)
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage($exceptionMessage);

        new Dsn($dsnString);
    }

    public static function invalidDsn(): iterable
    {
        yield [
            'leaflet://',
            'The map renderer DSN is invalid.',
        ];

        yield [
            '//default',
            'The map renderer DSN must contain a scheme.',
        ];
    }
}
