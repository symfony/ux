<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * The template can be updated by PreRender event listeners.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ComponentEventTest extends KernelTestCase
{
    #[DataProvider('provideFooBarSyntaxes')]
    public function testTemplateIsUpdatedByEventListener(string $syntax)
    {
        /** @var Environment $environment */
        $environment = self::getContainer()->get(Environment::class);
        $environment->setLoader(new ArrayLoader([
            'components/FooBar/Baz.foo_bar.html.twig' => 'updated',
            'components/FooBar/Baz.html.twig' => 'original',
        ]));

        $component = $environment->createTemplate($syntax);
        $result = $component->render();

        self::assertSame('updated', $result);
    }

    public function testEventsAreDispatchedForAnonymousComponentsWhenListenersExist(): void
    {
        $dispatched = [];
        $listener = static function (object $event) use (&$dispatched) {
            $dispatched[] = $event::class;
        };
        $dispatcher = self::getContainer()->get('event_dispatcher');
        foreach ([PreCreateForRenderEvent::class, PreMountEvent::class, PostMountEvent::class, PreRenderEvent::class, PostRenderEvent::class] as $eventClass) {
            $dispatcher->addListener($eventClass, $listener);
        }

        self::getContainer()->get(Environment::class)
            ->createTemplate('{{ component("BasicComponent") }}')
            ->render();

        self::assertSame([
            PreCreateForRenderEvent::class,
            PreMountEvent::class,
            PostMountEvent::class,
            PreRenderEvent::class,
            PostRenderEvent::class,
        ], $dispatched);
    }

    public static function provideFooBarSyntaxes(): iterable
    {
        yield 'TWIG component tag' => ['{% component "FooBar:Baz" %}{% endcomponent %}'];
        yield 'TWIG component function' => ['{{ component("FooBar:Baz") }}'];
        yield 'HTML self-closing tag' => ['<twig:FooBar:Baz />'];
        yield 'HTML open-close tag' => ['<twig:FooBar:Baz></twig:FooBar:Baz>'];
    }
}
