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
    public function testToArray(): void
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
        $collection->setQueryUrlMapping([
            'foo' => ['name' => 'foo'],
            'bar' => ['name' => 'bar'],
        ]);

        $expected = [
            'data-controller' => 'live',
            'data-live-name-value' => 'my-component',
            'id' => 'the-live-id',
            'data-live-fingerprint-value' => 'the-fingerprint',
            'data-live-props-value' => '{"the":"props"}',
            'data-live-url-value' => 'the-live-url',
            'data-live-listeners-value' => '{"event_name":"theActionName"}',
            'data-live-events-to-emit-value' => '[{"event":"event_name1","data":{"the":"data"},"target":"up","componentName":"the-component"},{"event":"event_name2","data":{"the":"data"},"target":null,"componentName":null}]',
            'data-live-query-mapping-value' => '{"foo":{"name":"foo"},"bar":{"name":"bar"}}',
        ];

        $this->assertSame($expected, $collection->toArray());
    }
}
