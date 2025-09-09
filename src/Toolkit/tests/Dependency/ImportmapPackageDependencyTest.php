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
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;

class ImportmapPackageDependencyTest extends TestCase
{
    public function testShouldBeInstantiable()
    {
        $dependency = new ImportmapPackageDependency('react');
        $this->assertSame('react', $dependency->package);
        $this->assertSame('Importmap package "react"', $dependency->toDebug());
        $this->assertSame('react', (string) $dependency);

        $dependency = new ImportmapPackageDependency('bootstrap/dist/css/bootstrap.min.css');
        $this->assertSame('bootstrap/dist/css/bootstrap.min.css', $dependency->package);
        $this->assertSame('Importmap package "bootstrap/dist/css/bootstrap.min.css"', $dependency->toDebug());
        $this->assertSame('bootstrap/dist/css/bootstrap.min.css', (string) $dependency);
    }
}
