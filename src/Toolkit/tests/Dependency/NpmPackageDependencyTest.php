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
use Symfony\UX\Toolkit\Dependency\ConstraintVersion;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;

class NpmPackageDependencyTest extends TestCase
{
    public function testShouldBeInstantiable()
    {
        $dependency = new NpmPackageDependency('react');
        $this->assertSame('react', $dependency->name);
        $this->assertNull($dependency->constraintVersion);
        $this->assertSame('NPM package "react"', $dependency->toDebug());
        $this->assertSame('react', (string) $dependency);

        $dependency = new NpmPackageDependency('react', new ConstraintVersion('^18.0.0'));
        $this->assertSame('react', $dependency->name);
        $this->assertSame('NPM package "react:^18.0.0"', $dependency->toDebug());
        $this->assertSame('react:^18.0.0', (string) $dependency);
    }

    public function testShouldFailIfPackageNameIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid NPM package name "/foo".');

        new NpmPackageDependency('/foo');
    }
}
