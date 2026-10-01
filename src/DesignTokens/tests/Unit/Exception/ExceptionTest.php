<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Exception\ExceptionInterface;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\LogicException;
use Symfony\UX\DesignTokens\Exception\ResolverException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Exception\TokenNotFoundException;
use Symfony\UX\DesignTokens\Exception\UnexpectedValueException;
use Symfony\UX\DesignTokens\Exception\UnresolvedReferenceException;

#[CoversClass(InvalidArgumentException::class)]
#[CoversClass(LogicException::class)]
#[CoversClass(ResolverException::class)]
#[CoversClass(RuntimeException::class)]
#[CoversClass(TokenNotFoundException::class)]
#[CoversClass(UnexpectedValueException::class)]
#[CoversClass(UnresolvedReferenceException::class)]
final class ExceptionTest extends TestCase
{
    /** @return iterable<string, array{class-string<\Throwable>, class-string<\Throwable>}> */
    public static function exceptions(): iterable
    {
        yield 'invalid argument' => [InvalidArgumentException::class, \InvalidArgumentException::class];
        yield 'logic' => [LogicException::class, \LogicException::class];
        yield 'runtime' => [RuntimeException::class, \RuntimeException::class];
        yield 'unexpected value' => [UnexpectedValueException::class, \UnexpectedValueException::class];
        yield 'resolver' => [ResolverException::class, \InvalidArgumentException::class];
        yield 'token not found' => [TokenNotFoundException::class, \InvalidArgumentException::class];
        yield 'unresolved reference' => [UnresolvedReferenceException::class, \RuntimeException::class];
    }

    /**
     * @param class-string<\Throwable> $class
     * @param class-string<\Throwable> $spl
     */
    #[DataProvider('exceptions')]
    public function testEveryExceptionIsCatchableThroughTheMarkerInterface(string $class, string $spl): void
    {
        self::assertTrue(is_a($class, ExceptionInterface::class, true), \sprintf('"%s" should implement the package marker interface.', $class));
        self::assertTrue(is_a($class, $spl, true), \sprintf('"%s" should stay a "%s".', $class, $spl));
    }

    public function testEveryExceptionInTheNamespaceIsCovered(): void
    {
        $declared = array_map(
            static fn (string $file): string => basename($file, '.php'),
            glob(\dirname(__DIR__, 3).'/src/Exception/*.php') ?: [],
        );
        $tested = array_map(
            static fn (array $case): string => new \ReflectionClass($case[0])->getShortName(),
            iterator_to_array(self::exceptions(), false),
        );

        sort($declared);
        $tested[] = 'ExceptionInterface';
        sort($tested);

        self::assertSame($declared, $tested, 'A new exception class must be added to the data provider.');
    }

    public function testTokenNotFoundExceptionCarriesThePathThatDidNotResolve(): void
    {
        $exception = new TokenNotFoundException('color.action.primary');

        self::assertSame('color.action.primary', $exception->getPath());
        self::assertSame('Design token not found: "color.action.primary".', $exception->getMessage());
    }

    public function testTokenNotFoundExceptionAcceptsAMoreSpecificMessage(): void
    {
        $exception = new TokenNotFoundException('color', '"color" is a token group, not a token.');

        self::assertSame('color', $exception->getPath());
        self::assertSame('"color" is a token group, not a token.', $exception->getMessage());
    }

    public function testResolverExceptionKeepsEveryErrorItWasGiven(): void
    {
        $exception = new ResolverException(['first problem', 'second problem']);

        self::assertSame(['first problem', 'second problem'], $exception->getErrors());
        self::assertSame("first problem\nsecond problem", $exception->getMessage());
    }
}
