<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Exception\ExceptionInterface;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\InvalidCursorException;
use Symfony\UX\Pagination\Exception\NavigationTooLargeException;
use Symfony\UX\Pagination\Exception\OffsetLimitExceededException;
use Symfony\UX\Pagination\Exception\OutOfRangePageException;
use Symfony\UX\Pagination\Exception\RuntimeException;
use Symfony\UX\Pagination\Exception\UnsupportedDoctrineQueryException;
use Symfony\UX\Pagination\Navigation\Navigation;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;

#[CoversNothing]
final class ExceptionInterfaceTest extends TestCase
{
    #[DataProvider('providePublicExceptions')]
    public function testPublicExceptionImplementsPackageMarker(string $exception): void
    {
        self::assertTrue(is_subclass_of($exception, ExceptionInterface::class));
    }

    public function testPackageErrorsCanBeCaughtByTheMarker(): void
    {
        try {
            new Navigation(1, 10, new PaginationUrlGenerator(), modeParameter: -1);
            self::fail('Expected a pagination exception.');
        } catch (ExceptionInterface $exception) {
            self::assertInstanceOf(InvalidArgumentException::class, $exception);
        }
    }

    /**
     * @return iterable<string, array{class-string<\Throwable>}>
     */
    public static function providePublicExceptions(): iterable
    {
        yield InvalidArgumentException::class => [InvalidArgumentException::class];
        yield InvalidCursorException::class => [InvalidCursorException::class];
        yield NavigationTooLargeException::class => [NavigationTooLargeException::class];
        yield OffsetLimitExceededException::class => [OffsetLimitExceededException::class];
        yield OutOfRangePageException::class => [OutOfRangePageException::class];
        yield RuntimeException::class => [RuntimeException::class];
        yield UnsupportedDoctrineQueryException::class => [UnsupportedDoctrineQueryException::class];
    }
}
