<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Token;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Token\AbstractToken;
use Symfony\UX\DesignTokens\Token\Css\CssValue;

#[CoversClass(AbstractToken::class)]
final class AbstractTokenTest extends TestCase
{
    public function testConstructorStoresValue(): void
    {
        $token = new TestToken('hello');

        self::assertSame('hello', $token->getValue());
    }

    public function testConstructorDefaultsDescriptionToNull(): void
    {
        $token = new TestToken('hello');

        self::assertNull($token->getDescription());
    }

    public function testConstructorDefaultsExtensionsToEmptyArray(): void
    {
        $token = new TestToken('hello');

        self::assertSame([], $token->getExtensions());
    }

    public function testConstructorStoresDescription(): void
    {
        $token = new TestToken('hello', 'A description');

        self::assertSame('A description', $token->getDescription());
    }

    public function testConstructorStoresExtensions(): void
    {
        $extensions = ['com.figma' => ['styleId' => 'S:abc123']];
        $token = new TestToken('hello', null, $extensions);

        self::assertSame($extensions, $token->getExtensions());
    }

    #[DataProvider('deprecationProvider')]
    public function testDeprecationSplitsTheFlagFromTheMessage(bool|string|null $deprecated, bool $expectedFlag, ?string $expectedMessage): void
    {
        $token = new TestToken(1, deprecated: $deprecated);

        self::assertSame($expectedFlag, $token->isDeprecated());
        self::assertSame($expectedMessage, $token->getDeprecationMessage());
    }

    /** @return iterable<string, array{bool|string|null, bool, ?string}> */
    public static function deprecationProvider(): iterable
    {
        yield 'absent' => [null, false, null];
        yield 'undeprecated' => [false, false, null];
        yield 'flag' => [true, true, null];
        yield 'message' => ['Use space.new.', true, 'Use space.new.'];
        yield 'empty message' => ['', true, null];
    }

    #[DataProvider('valueProvider')]
    public function testConstructorAcceptsAnyValueType(mixed $value): void
    {
        $token = new TestToken($value);

        self::assertSame($value, $token->getValue());
    }

    /** @return iterable<string, array{mixed}> */
    public static function valueProvider(): iterable
    {
        yield 'string' => ['solid'];
        yield 'integer' => [42];
        yield 'float' => [1.618];
        yield 'array' => [['a', 'b']];
        yield 'null' => [null];
    }
}

/** @extends AbstractToken<mixed> */
final class TestToken extends AbstractToken
{
    public function getType(): string
    {
        return 'test';
    }

    public function __toString(): string
    {
        return CssValue::stringify($this->getValue());
    }
}
