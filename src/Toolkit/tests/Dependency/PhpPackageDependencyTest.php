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
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\Version;

final class PhpPackageDependencyTest extends TestCase
{
    public function testShouldBeInstantiable(): void
    {
        $dependency = new PhpPackageDependency('twig/html-extra');
        $this->assertSame('twig/html-extra', $dependency->name);
        $this->assertNull($dependency->constraintVersion);
        $this->assertSame('twig/html-extra', (string) $dependency);

        $dependency = new PhpPackageDependency('twig/html-extra', new Version(3, 2, 1));
        $this->assertSame('twig/html-extra', $dependency->name);
        $this->assertSame('twig/html-extra:^3.2.1', (string) $dependency);
    }

    public function testShouldFailIfPackageNameIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid PHP package name "/foo".');

        new PhpPackageDependency('/foo');
    }
}
