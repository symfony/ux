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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\Pagination\Exception\OutOfRangePageException;

#[CoversClass(OutOfRangePageException::class)]
final class OutOfRangePageExceptionTest extends TestCase
{
    public function testPagesAreExposed(): void
    {
        $exception = new OutOfRangePageException(12, 5);

        self::assertSame(12, $exception->requestedPage);
        self::assertSame(5, $exception->lastPage);
    }

    public function testMessageContainsPages(): void
    {
        $exception = new OutOfRangePageException(12, 5);

        self::assertStringContainsString('12', $exception->getMessage());
        self::assertStringContainsString('5', $exception->getMessage());
    }

    public function testIsANotFoundHttpException(): void
    {
        $exception = new OutOfRangePageException(2, 1);

        self::assertInstanceOf(NotFoundHttpException::class, $exception);
    }
}
