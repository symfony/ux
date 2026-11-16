<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component2;
use Symfony\UX\TwigComponent\ComponentFactory;

final class LiveComponentMetadataFactoryTest extends TestCase
{
    public function testReadsTheHookMethodsListedWhenTheContainerWasCompiled(): void
    {
        $factory = $this->createFactory([
            Component2::class => [
                'pre_dehydrate' => ['compiledPreDehydrate'],
                'post_hydrate' => ['compiledPostHydrate'],
                'pre_re_render' => ['compiledPreReRender'],
                'actions' => ['compiledAction'],
                'listeners' => [['action' => 'compiledAction', 'event' => 'compiledEvent']],
            ],
        ]);
        $component = new Component2();

        $hookMethods = [
            'pre_dehydrate' => $factory->preDehydrateMethods($component),
            'post_hydrate' => $factory->postHydrateMethods($component),
            'pre_re_render' => $factory->preReRenderMethods($component),
            'compiled_action_allowed' => $factory->isActionAllowed($component, 'compiledAction'),
            'increase_allowed' => $factory->isActionAllowed($component, 'increase'),
            'listeners' => $factory->liveListeners($component),
        ];

        $this->assertSame(
            [
                'pre_dehydrate' => ['compiledPreDehydrate'],
                'post_hydrate' => ['compiledPostHydrate'],
                'pre_re_render' => ['compiledPreReRender'],
                'compiled_action_allowed' => true,
                'increase_allowed' => false,
                'listeners' => [['action' => 'compiledAction', 'event' => 'compiledEvent']],
            ],
            $hookMethods
        );
    }

    public function testFindsTheHookMethodsOfAClassTheContainerDoesNotDescribe(): void
    {
        $factory = $this->createFactory([]);
        $component = new Component2();

        $hookMethods = [
            'pre_dehydrate' => $factory->preDehydrateMethods($component),
            'post_hydrate' => $factory->postHydrateMethods($component),
            'pre_re_render' => $factory->preReRenderMethods($component),
            'increase_allowed' => $factory->isActionAllowed($component, 'increase'),
            'default_action_allowed' => $factory->isActionAllowed($component, 'defaultAction'),
            'listeners' => $factory->liveListeners($component),
        ];

        $this->assertSame(
            [
                'pre_dehydrate' => ['preDehydrateMethod'],
                'post_hydrate' => ['postHydrateMethod'],
                'pre_re_render' => ['preReRenderMethod'],
                'increase_allowed' => true,
                'default_action_allowed' => false,
                'listeners' => [
                    ['action' => 'increaseEvent1', 'event' => 'triggerIncrease'],
                    ['action' => 'increaseEvent2', 'event' => 'triggerIncrease'],
                ],
            ],
            $hookMethods
        );
    }

    private function createFactory(array $hookMethods): LiveComponentMetadataFactory
    {
        return new LiveComponentMetadataFactory(
            new \ReflectionClass(ComponentFactory::class)->newInstanceWithoutConstructor(),
            $this->createStub(PropertyTypeExtractorInterface::class),
            TypeResolver::create(),
            hookMethods: $hookMethods,
        );
    }
}
