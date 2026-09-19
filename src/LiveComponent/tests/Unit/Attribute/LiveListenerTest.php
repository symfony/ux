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
    public function testGetEventNameWithString()
    {
        $listener = new LiveListener('item_updated');

        $this->assertSame('item_updated', $listener->getEventName());
    }

    public function testGetEventNameWithBackedEnum()
    {
        $listener = new LiveListener(TestEvent::ItemUpdated);

        $this->assertSame('item_updated', $listener->getEventName());
    }
}

enum TestEvent: string
{
    case ItemUpdated = 'item_updated';
    case ItemDeleted = 'item_deleted';
}
