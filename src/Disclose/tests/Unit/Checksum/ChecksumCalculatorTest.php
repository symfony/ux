<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Unit\Checksum;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Disclose\Checksum\ChecksumCalculator;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class ChecksumCalculatorTest extends TestCase
{
    public function testIsStable(): void
    {
        $calculator = new ChecksumCalculator('secret');

        self::assertSame($calculator->calculateForArray(['a' => 1]), $calculator->calculateForArray(['a' => 1]));
    }

    public function testIsOrderIndependent(): void
    {
        $calculator = new ChecksumCalculator('secret');

        self::assertSame(
            $calculator->calculateForArray(['b' => 2, 'a' => 1]),
            $calculator->calculateForArray(['a' => 1, 'b' => 2]),
        );
    }

    public function testDiffersAcrossDataSets(): void
    {
        $calculator = new ChecksumCalculator('secret');

        self::assertNotSame($calculator->calculateForArray(['a' => 1]), $calculator->calculateForArray(['a' => 2]));
    }

    public function testDiffersAcrossSecrets(): void
    {
        $data = ['class' => 'App\Entity\User', 'id' => 42];

        self::assertNotSame(
            new ChecksumCalculator('secret-a')->calculateForArray($data),
            new ChecksumCalculator('secret-b')->calculateForArray($data),
        );
    }
}
