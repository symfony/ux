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
use Symfony\UX\Toolkit\Dependency\StimulusControllerDependency;

final class StimulusControllerDependencyTest extends TestCase
{
    public function testShouldBeInstantiable(): void
    {
        $dependency = new StimulusControllerDependency('clipboard');

        $this->assertSame('clipboard', $dependency->name);
        $this->assertSame('clipboard', (string) $dependency);
    }

    public function testShouldFailIfComponentNameIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Stimulus controller name "my_Controller".');

        new StimulusControllerDependency('my_Controller');
    }
}
