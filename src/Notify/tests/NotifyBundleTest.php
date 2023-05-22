<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Notify\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Notify\Tests\Kernel\TwigAppKernel;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
class NotifyBundleTest extends TestCase
{
    public function testBootKernel()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $this->assertArrayHasKey('NotifyBundle', $kernel->getBundles());
    }
}
