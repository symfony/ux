<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\VendorVersions;
use PHPUnit\Framework\TestCase;

final class VendorVersionsTest extends TestCase
{
    public function testItReadsThePrettyVersionsOfAVendor(): void
    {
        $versions = VendorVersions::read(__DIR__.'/../Fixtures/installed');

        self::assertSame('8.2.0', $versions->symfony);
        self::assertSame('3.30.0', $versions->twig);
        self::assertSame('', $versions->tailwindExtra);
    }
}
