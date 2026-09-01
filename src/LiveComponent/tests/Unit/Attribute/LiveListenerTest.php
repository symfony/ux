<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Attribute\LiveListener;

class LiveListenerTest extends TestCase
{
    public function testPlainEventNameHasNoCondition()
    {
        $listener = new LiveListener('product_updated');

        $this->assertSame('product_updated', $listener->getEventName());
        $this->assertNull($listener->getCondition());
    }

    public function testEventNameWithCondition()
    {
        $listener = new LiveListener('product_updated(event.id == props.product)');

        $this->assertSame('product_updated', $listener->getEventName());
        $this->assertSame('event.id == props.product', $listener->getCondition());
    }

    public function testConditionCanContainNestedParentheses()
    {
        $listener = new LiveListener('product_updated(min(props.a, props.b) == event.id)');

        $this->assertSame('product_updated', $listener->getEventName());
        $this->assertSame('min(props.a, props.b) == event.id', $listener->getCondition());
    }

    public function testWhitespaceIsTrimmed()
    {
        $listener = new LiveListener('  product_updated ( event.id == props.product ) ');

        $this->assertSame('product_updated', $listener->getEventName());
        $this->assertSame('event.id == props.product', $listener->getCondition());
    }

    public function testEmptyParenthesesResultInNoCondition()
    {
        $listener = new LiveListener('product_updated()');

        $this->assertSame('product_updated', $listener->getEventName());
        $this->assertNull($listener->getCondition());
    }

    public function testInvalidSyntaxThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        new LiveListener('product_updated(event.id == props.product');
    }
}
