<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Util\FingerprintCalculator;

final class FingerprintCalculatorTest extends TestCase
{
    public function testConstructWithEmptySecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A non-empty secret is required.');

        new FingerprintCalculator('');
    }
}
