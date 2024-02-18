<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\UX\LiveComponent\EventListener\DeferLiveComponentSubscriber;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\Event\PostMountEvent;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class DeferLiveComponentSubscriberTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testLoadingAttributeIsExtracted()
    {
        $subscriber = new DeferLiveComponentSubscriber();
        $event = $this->createPostMountEvent(['loading' => 'lazy']);

        $subscriber->onPostMount($event);

        $this->assertArrayHasKey('loading', $event->getExtraMetadata());
        $this->assertSame('lazy', $event->getExtraMetadata()['loading']);
        $this->assertArrayNotHasKey('loading', $event->getData());
    }

    /**
     * @group legacy
     */
    public function testLoadingAttributeOverrideDeferAttribute()
    {
        $subscriber = new DeferLiveComponentSubscriber();
        $event = $this->createPostMountEvent(['loading' => 'lazy', 'defer' => true]);

        $this->expectDeprecation('Since symfony/ux-live-component 2.17: The "defer" attribute is deprecated and will be removed in 3.0. Use the "loading" attribute instead set to the value "defer".');

        $subscriber->onPostMount($event);

        $this->assertArrayHasKey('loading', $event->getExtraMetadata());
        $this->assertSame('lazy', $event->getExtraMetadata()['loading']);
    }

    /**
     * @group legacy
     */
    public function testDeferAttributeTriggerDeprecation()
    {
        $subscriber = new DeferLiveComponentSubscriber();
        $event = $this->createPostMountEvent([
            'defer' => true,
        ]);

        $this->expectDeprecation('Since symfony/ux-live-component 2.17: The "defer" attribute is deprecated and will be removed in 3.0. Use the "loading" attribute instead set to the value "defer".');

        $subscriber->onPostMount($event);
    }

    public function testLoadingAttributesAreRemoved()
    {
        $subscriber = new DeferLiveComponentSubscriber();
        $event = $this->createPostMountEvent([
            'loading' => null,
            'loading-template' => null,
            'loading-tag' => null,
        ]);

        $subscriber->onPostMount($event);

        $this->assertArrayNotHasKey('loading', $event->getData());
        $this->assertArrayNotHasKey('loading-template', $event->getData());
        $this->assertArrayNotHasKey('loading-tag', $event->getData());
    }

    /**
     * @dataProvider provideInvalidLoadingValues
     */
    public function testInvalidLoadingValuesThrows(mixed $value)
    {
        $subscriber = new DeferLiveComponentSubscriber();
        $event = $this->createPostMountEvent([
            'loading' => $value,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $subscriber->onPostMount($event);
    }

    public static function provideInvalidLoadingValues()
    {
        return [
            ['foo'],
            [true],
            [['foo']],
            ['false'],
        ];
    }

    private function createPostMountEvent(array $data): PostMountEvent
    {
        $componentMetadata = new ComponentMetadata([]);
        $event = new PostMountEvent(new \stdClass(), $data, $componentMetadata);
        $event->setData($data);

        return $event;
    }
}
