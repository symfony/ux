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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Dependency\ConstraintVersion;

final class ConstraintVersionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $version = new ConstraintVersion('1.2.3');

        $this->assertSame('1.2.3', (string) $version);
    }

    #[DataProvider('provideInvalidVersions')]
    public function testCannotBeInstantiatedWithInvalidVersion(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConstraintVersion($value);
    }

    public static function provideInvalidVersions(): iterable
    {
        yield ['^1 --no-plugins'];
        yield ['1; id'];
        yield ["1\r2"];
        yield ['</><fg=black>1'];
    }

    public function testCanBeCompared(): void
    {
        $this->assertTrue(new ConstraintVersion('1.2.3')->isHigherThan(new ConstraintVersion('1.2.2')));
        $this->assertFalse(new ConstraintVersion('1.2.3')->isHigherThan(new ConstraintVersion('1.2.4')));
        $this->assertTrue(new ConstraintVersion('1.2.3')->isHigherThan(new ConstraintVersion('1.1.99')));
        $this->assertFalse(new ConstraintVersion('1.2.3')->isHigherThan(new ConstraintVersion('1.2.3')));
        $this->assertTrue(new ConstraintVersion('1.2.3')->isHigherThan(new ConstraintVersion('0.99.99')));
        $this->assertFalse(new ConstraintVersion('1.2.3')->isHigherThan(new ConstraintVersion('2.0.0')));
    }
}
