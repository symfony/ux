<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Component;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Component\PhpStanTypeValidator;

class PhpStanTypeValidatorTest extends TestCase
{
    /**
     * @return iterable<array{string}>
     */
    public static function provideValidTypes(): iterable
    {
        yield ['string'];
        yield ['boolean'];
        yield ['number'];
        yield ['int|null'];
        yield ["'default'|'line'"];
        yield ["'default'|'secondary'|'destructive'|'outline'|'ghost'|'link'"];
        yield ['string|array<string>|null'];
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideInvalidTypes(): iterable
    {
        yield 'empty' => [''];
        yield 'trailing pipe' => ['string|'];
        yield 'unbalanced generic' => ['array<string'];
        yield 'trailing const pipe' => ["'default'|"];
    }

    #[DataProvider('provideValidTypes')]
    public function testValidTypesAreAccepted(string $type)
    {
        self::assertTrue(new PhpStanTypeValidator()->isValid($type));
    }

    #[DataProvider('provideInvalidTypes')]
    public function testInvalidTypesAreRejected(string $type)
    {
        self::assertFalse(new PhpStanTypeValidator()->isValid($type));
    }
}
