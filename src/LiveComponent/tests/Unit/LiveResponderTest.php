<?php

namespace Symfony\UX\LiveComponent\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\LiveResponder;

class LiveResponderTest extends TestCase
{
    public function testEmit(): void
    {
        $responder = new LiveResponder();
        $responder->emit('event_name1', ['data_key' => 'data_value']);
        $responder->emit('event_name2', ['data_key' => 'data_value']);

        $this->assertSame([
            [
                'event' => 'event_name1',
                'data' => ['data_key' => 'data_value'],
                'target' => null,
                'componentName' => null,
            ],
            [
                'event' => 'event_name2',
                'data' => ['data_key' => 'data_value'],
                'target' => null,
                'componentName' => null,
            ],
        ], $responder->getEventsToEmit());
    }

    public function testEmitUp(): void
    {
        $responder = new LiveResponder();
        $responder->emitUp('event_name1', ['data_key' => 'data_value']);
        $responder->emitUp('event_name2', ['data_key' => 'data_value']);

        $this->assertSame([
            [
                'event' => 'event_name1',
                'data' => ['data_key' => 'data_value'],
                'target' => 'up',
                'componentName' => null,
            ],
            [
                'event' => 'event_name2',
                'data' => ['data_key' => 'data_value'],
                'target' => 'up',
                'componentName' => null,
            ],
        ], $responder->getEventsToEmit());
    }

    public function testEmitSelf(): void
    {
        $responder = new LiveResponder();
        $responder->emitSelf('event_name1', ['data_key' => 'data_value']);
        $responder->emitSelf('event_name2', ['data_key' => 'data_value']);

        $this->assertSame([
            [
                'event' => 'event_name1',
                'data' => ['data_key' => 'data_value'],
                'target' => 'self',
                'componentName' => null,
            ],
            [
                'event' => 'event_name2',
                'data' => ['data_key' => 'data_value'],
                'target' => 'self',
                'componentName' => null,
            ],
        ], $responder->getEventsToEmit());
    }

    public function testDispatchBrowserEvent(): void
    {
        $responder = new LiveResponder();
        $responder->dispatchBrowserEvent('event_name1', ['data_key' => 'data_value']);
        $responder->dispatchBrowserEvent('event_name2', ['data_key' => 'data_value']);
        $this->assertSame([
            [
                'event' => 'event_name1',
                'payload' => ['data_key' => 'data_value'],
            ],
            [
                'event' => 'event_name2',
                'payload' => ['data_key' => 'data_value'],
            ],
        ], $responder->getBrowserEventsToDispatch());
        $responder->reset();
        $this->assertSame([], $responder->getBrowserEventsToDispatch());
    }
}
