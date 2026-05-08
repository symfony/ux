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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\EventListener\DeferLiveComponentSubscriber;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Symfony\UX\TwigComponent\MountedComponent;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class DeferLiveComponentSubscriberTest extends TestCase
{
    public function testLoadingAttributeIsExtracted()
    {
        $subscriber = new DeferLiveComponentSubscriber();
        $event = $this->createPostMountEvent(['loading' => 'lazy']);

        $subscriber->onPostMount($event);

        $this->assertArrayHasKey('loading', $event->getExtraMetadata());
        $this->assertSame('lazy', $event->getExtraMetadata()['loading']);
        $this->assertArrayNotHasKey('loading', $event->getData());
    }

    public function testLoadingAttributeIsNotExtractedWhenComponentIsNotLive()
    {
        $data = ['loading' => 'lazy'];
        $event = new PostMountEvent(new \stdClass(), $data, new ComponentMetadata([]));
        $event->setData($data);

        $subscriber = new DeferLiveComponentSubscriber();
        $subscriber->onPostMount($event);

        $this->assertArrayNotHasKey('loading', $event->getExtraMetadata());
        $this->assertArrayHasKey('loading', $event->getData());
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

    #[DataProvider('provideInvalidLoadingValues')]
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

    public function testOnPreRenderUsesEventTemplateInsteadOfMetadataTemplate()
    {
        $subscriber = new DeferLiveComponentSubscriber();

        $metadata = new ComponentMetadata(['template' => 'original_metadata_template.html.twig']);

        $escaper = new EscaperRuntime();
        $attributes = new ComponentAttributes([], $escaper);

        $mountedComponent = new MountedComponent(
            'test_component',
            $metadata,
            $attributes,
            [],
            ['loading' => 'lazy']
        );

        $event = new PreRenderEvent($mountedComponent, $metadata, ['existing_var' => 'value']);

        $event->setTemplate('dynamically_changed_template.html.twig');

        $subscriber->onPreRender($event);

        $this->assertSame('@LiveComponent/deferred.html.twig', $event->getTemplate());

        $variables = $event->getVariables();
        $this->assertArrayHasKey('componentTemplate', $variables);
        $this->assertSame('dynamically_changed_template.html.twig', $variables['componentTemplate']);

        $this->assertSame('lazy', $variables['loading']);
        $this->assertSame('value', $variables['existing_var']);
    }

    public function testOnPreRenderDoesNothingWhenNoLoadingMetadata()
    {
        $subscriber = new DeferLiveComponentSubscriber();

        $metadata = new ComponentMetadata(['template' => 'original_template.html.twig']);

        $escaper = new EscaperRuntime();
        $attributes = new ComponentAttributes([], $escaper);

        $mountedComponent = new MountedComponent(
            'test_component',
            $metadata,
            $attributes,
            [],
            []
        );

        $event = new PreRenderEvent($mountedComponent, $metadata, []);

        $subscriber->onPreRender($event);

        $this->assertSame('original_template.html.twig', $event->getTemplate());
    }

    private function createPostMountEvent(array $data): PostMountEvent
    {
        $componentMetadata = new ComponentMetadata(['live' => true]);
        $event = new PostMountEvent(new \stdClass(), $data, $componentMetadata);
        $event->setData($data);

        return $event;
    }
}
