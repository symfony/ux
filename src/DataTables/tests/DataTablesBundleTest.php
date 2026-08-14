<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTables\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTables\Tests\Kernel\TwigAppKernel;

class DataTablesBundleTest extends TestCase
{
    public function testBootKernel()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $this->assertArrayHasKey('DataTablesBundle', $kernel->getBundles());
    }
}
