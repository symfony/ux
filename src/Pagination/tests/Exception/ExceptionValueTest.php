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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Exception\InvalidCursorException;
use Symfony\UX\Pagination\Exception\NavigationTooLargeException;
use Symfony\UX\Pagination\Exception\OffsetLimitExceededException;

#[CoversClass(InvalidCursorException::class)]
#[CoversClass(NavigationTooLargeException::class)]
#[CoversClass(OffsetLimitExceededException::class)]
final class ExceptionValueTest extends TestCase
{
    public function testInvalidCursorPreservesMessageAndPreviousException()
    {
        $previous = new \RuntimeException('decoder failed');
        $exception = new InvalidCursorException('Bad cursor.', $previous);

        self::assertSame('Bad cursor.', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(400, $exception->getStatusCode());
    }

    public function testOffsetLimitPreservesContext()
    {
        $exception = new OffsetLimitExceededException(51, 20, 1000);

        self::assertSame(51, $exception->page);
        self::assertSame(20, $exception->perPage);
        self::assertSame(1000, $exception->maximumOffset);
        self::assertStringContainsString('cursor pagination', $exception->getMessage());
        self::assertSame(400, $exception->getStatusCode());
    }

    public function testNavigationTooLargeExplainsTheLimit()
    {
        $exception = new NavigationTooLargeException(500, 100);

        self::assertStringContainsString('500 pagination links', $exception->getMessage());
        self::assertStringContainsString('limited to 100 pages', $exception->getMessage());
    }
}
