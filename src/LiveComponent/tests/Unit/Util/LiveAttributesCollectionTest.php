<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Util;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Util\LiveAttributesCollection;

class LiveAttributesCollectionTest extends KernelTestCase
{
    public function testToEscapedArray(): void
    {
        self::bootKernel();
        $collection = new LiveAttributesCollection(self::getContainer()->get('twig'));
        // call all setter methods on $collection to create a great test case
        // pass descriptive values to each setter method
        $collection->setLiveController('my-component');
        $collection->setLiveId('the-live-id');
        $collection->setFingerprint('the-fingerprint');
        $collection->setProps(['the' => 'props']);
        $collection->setUrl('the-live-url');
        $collection->setCsrf('the-csrf-token');
        $collection->setListeners(['event_name' => 'theActionName']);
        $collection->setEventsToEmit([
            [
                'event' => 'event_name1',
                'data' => ['the' => 'data'],
                'target' => 'up',
                'componentName' => 'the-component',
            ],
            [
                'event' => 'event_name2',
                'data' => ['the' => 'data'],
                'target' => null,
                'componentName' => null,
            ],
        ]);

        $expected = [
            'data-controller' => 'live',
            'data-live-name-value' => 'my-component',
            'data-live-id' => 'the-live-id',
            'data-live-fingerprint-value' => 'the-fingerprint',
            'data-live-props-value' => '&#x7B;&quot;the&quot;&#x3A;&quot;props&quot;&#x7D;',
            'data-live-url-value' => 'the-live-url',
            'data-live-csrf-value' => 'the-csrf-token',
            'data-live-listeners-value' => '&#x7B;&quot;event_name&quot;&#x3A;&quot;theActionName&quot;&#x7D;',
            'data-live-emit' => '&#x5B;&#x7B;&quot;event&quot;&#x3A;&quot;event_name1&quot;,&quot;data&quot;&#x3A;&#x7B;&quot;the&quot;&#x3A;&quot;data&quot;&#x7D;,&quot;target&quot;&#x3A;&quot;up&quot;,&quot;componentName&quot;&#x3A;&quot;the-component&quot;&#x7D;,&#x7B;&quot;event&quot;&#x3A;&quot;event_name2&quot;,&quot;data&quot;&#x3A;&#x7B;&quot;the&quot;&#x3A;&quot;data&quot;&#x7D;,&quot;target&quot;&#x3A;null,&quot;componentName&quot;&#x3A;null&#x7D;&#x5D;',
        ];

        $this->assertSame($expected, $collection->toEscapedArray());
    }
}
