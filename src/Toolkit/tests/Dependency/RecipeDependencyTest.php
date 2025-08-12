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
use Symfony\UX\Toolkit\Dependency\RecipeDependency;

final class RecipeDependencyTest extends TestCase
{
    public function testShouldBeInstantiable()
    {
        $dependency = new RecipeDependency('Table');
        $this->assertSame('Table', $dependency->name);
        $this->assertSame('Recipe "Table"', $dependency->toDebug());
        $this->assertSame('Table', (string) $dependency);
    }
}
