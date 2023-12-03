<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Unit\Calculator;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;

final class ChecksumCalculatorTest extends TestCase
{
    public function testCalculateChecksumForArray(): void
    {
        $this->assertSame(
            'tZ34YQKgpttqybzPws0YJCOHV1QtMjQeuyy+rszdhXU=',
            $this->createTestSubject()->calculateForArray(['test' => 'test']),
        );
    }

    public function testCalculateTheSameChecksumForTheSameArrayButInDifferentOrder(): void
    {
        $this->assertSame(
            $this->createTestSubject()->calculateForArray(['test' => 'test', 'test2' => 'test2']),
            $this->createTestSubject()->calculateForArray(['test2' => 'test2', 'test' => 'test']),
        );
    }

    private function createTestSubject(): ChecksumCalculator
    {
        return new ChecksumCalculator('s3cr3t');
    }
}
