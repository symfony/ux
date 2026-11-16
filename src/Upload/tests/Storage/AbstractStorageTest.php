<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Storage\AbstractStorage;
use Symfony\UX\Upload\Storage\InMemoryStorage;

#[CoversClass(AbstractStorage::class)]
final class AbstractStorageTest extends TestCase
{
    #[TestWith(['/absolute'], 'absolute path')]
    #[TestWith([''], 'empty')]
    #[TestWith(['/'], 'only a slash')]
    #[TestWith(['uploads/../completed'], 'parent segment')]
    #[TestWith(["uploads\0completed"], 'NUL byte')]
    public function testRejectsAnUnsafeCompletedPrefix(string $prefix): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('completed upload prefix');

        new InMemoryStorage($prefix);
    }
}
