<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Dependency;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Dependency\Version;

final class VersionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $version = new Version('1.2.3');

        $this->assertSame('1.2.3', (string) $version);
    }

    public function testCanBeCompared(): void
    {
        $this->assertTrue((new Version('1.2.3'))->isHigherThan(new Version('1.2.2')));
        $this->assertFalse((new Version('1.2.3'))->isHigherThan(new Version('1.2.4')));
        $this->assertTrue((new Version('1.2.3'))->isHigherThan(new Version('1.1.99')));
        $this->assertFalse((new Version('1.2.3'))->isHigherThan(new Version('1.2.3')));
        $this->assertTrue((new Version('1.2.3'))->isHigherThan(new Version('0.99.99')));
        $this->assertFalse((new Version('1.2.3'))->isHigherThan(new Version('2.0.0')));
    }
}
