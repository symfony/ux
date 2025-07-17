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
use Symfony\UX\Toolkit\Dependency\ComponentDependency;

final class ComponentDependencyTest extends TestCase
{
    public function testShouldBeInstantiable(): void
    {
        $dependency = new ComponentDependency('Table:Body');

        $this->assertSame('Table:Body', $dependency->name);
        $this->assertSame('Table:Body', (string) $dependency);
    }

    public function testShouldFailIfComponentNameIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid component name "foobar".');

        new ComponentDependency('foobar');
    }
}
