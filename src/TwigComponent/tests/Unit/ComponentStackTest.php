<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\MountedComponent;

final class ComponentStackTest extends TestCase
{
    public function testPushAndPopAndFetchingComponents(): void
    {
        $stack = new ComponentStack();
        $component1 = new MountedComponent('component1', new \stdClass(), new ComponentAttributes([]));
        $component2 = new MountedComponent('component2', new \stdClass(), new ComponentAttributes([]));

        $this->assertNull($stack->pop());
        $this->assertNull($stack->getCurrentComponent());
        $this->assertNull($stack->getParentComponent());

        $stack->push($component1);
        $this->assertSame($component1, $stack->getCurrentComponent());
        $this->assertNull($stack->getParentComponent());

        $stack->push($component2);
        $this->assertSame($component2, $stack->getCurrentComponent());
        $this->assertSame($component1, $stack->getParentComponent());

        $removedComponent = $stack->pop();
        $this->assertSame($component2, $removedComponent);
        $this->assertSame($component1, $stack->getCurrentComponent());
        $this->assertNull($stack->getParentComponent());

        $removedComponent = $stack->pop();
        $this->assertSame($component1, $removedComponent);
        $this->assertNull($stack->getCurrentComponent());
        $this->assertNull($stack->getParentComponent());
    }
}
