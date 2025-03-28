<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Exception\UnsupportedSchemeException;
use Symfony\UX\Map\Renderer\Dsn;
use Symfony\UX\Map\Renderer\RendererFactoryInterface;

/**
 * A test case to ease testing a renderer factory.
 *
 * @author Oskar Stark <oskarstark@googlemail.com>
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract class RendererFactoryTestCase extends TestCase
{
    abstract public function createRendererFactory(): RendererFactoryInterface;

    /**
     * @return iterable<array{0: bool, 1: string}>
     */
    abstract public static function supportsRenderer(): iterable;

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    abstract public static function createRenderer(): iterable;

    /**
     * @return iterable<array{0: string, 1: string|null}>
     */
    public static function unsupportedSchemeRenderer(): iterable
    {
        return [];
    }

    /**
     * @return iterable<array{0: string, 1: string|null}>
     */
    public static function incompleteDsnRenderer(): iterable
    {
        return [];
    }

    /**
     * @dataProvider supportsRenderer
     */
    #[DataProvider('supportsRenderer')]
    public function testSupports(bool $expected, string $dsn): void
    {
        $factory = $this->createRendererFactory();

        $this->assertSame($expected, $factory->supports(new Dsn($dsn)));
    }

    /**
     * @dataProvider createRenderer
     */
    #[DataProvider('createRenderer')]
    public function testCreate(string $expected, string $dsn): void
    {
        $factory = $this->createRendererFactory();
        $renderer = $factory->create(new Dsn($dsn));

        $this->assertSame($expected, (string) $renderer);
    }

    /**
     * @dataProvider unsupportedSchemeRenderer
     */
    #[DataProvider('unsupportedSchemeRenderer')]
    public function testUnsupportedSchemeException(string $dsn, ?string $message = null): void
    {
        $factory = $this->createRendererFactory();

        $dsn = new Dsn($dsn);

        $this->expectException(UnsupportedSchemeException::class);
        if (null !== $message) {
            $this->expectExceptionMessage($message);
        }

        $factory->create($dsn);
    }
}
