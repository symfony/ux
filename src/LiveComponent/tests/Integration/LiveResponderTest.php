<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\LiveResponder;

final class LiveResponderTest extends KernelTestCase
{
    public function testIsResetBetweenRequests(): void
    {
        /** @var LiveResponder $liveResponder */
        $liveResponder = self::getContainer()->get('ux.live_component.live_responder');

        // events of a LiveAction that redirects: no component is rendered to consume them
        $liveResponder->emit('event1', ['foo' => 'bar']);
        $liveResponder->dispatchBrowserEvent('browser-event', ['fooKey' => 'barVal']);

        self::getContainer()->get('services_resetter')->reset();

        $this->assertSame([], $liveResponder->getEventsToEmit());
        $this->assertSame([], $liveResponder->getBrowserEventsToDispatch());
    }
}
