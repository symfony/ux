<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentA;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\WithSlots;
use Symfony\UX\TwigComponent\Tests\Fixtures\Service\ServiceA;

final class InteractsWithTwigComponentsTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    /**
     * @dataProvider componentANameProvider
     */
    public function testCanMountComponent(string $name): void
    {
        $component = $this->mountTwigComponent($name, [
            'propA' => 'prop a value',
            'propB' => 'prop b value',
        ]);

        $this->assertInstanceof(ComponentA::class, $component);
        $this->assertInstanceOf(ServiceA::class, $component->getService());
        $this->assertSame('prop a value', $component->propA);
        $this->assertSame('prop b value', $component->getPropB());
    }

    /**
     * @dataProvider componentANameProvider
     */
    public function testCanRenderComponent(string $name): void
    {
        $rendered = $this->renderTwigComponent($name, [
            'propA' => 'prop a value',
            'propB' => 'prop b value',
        ]);

        $this->assertStringContainsString('propA: prop a value', $rendered);
        $this->assertStringContainsString('propB: prop b value', $rendered);
        $this->assertStringContainsString('service: service a value', $rendered);
        $this->assertCount(2, $rendered->crawler()->filter('ul li'));
    }

    /**
     * @dataProvider withSlotsNameProvider
     */
    public function testCanRenderComponentWithSlots(string $name): void
    {
        $rendered = $this->renderTwigComponent(
            name: $name,
            content: '<p>some content</p>',
            blocks: [
                'slot1' => '<p>some slot1 content</p>',
                'slot2' => $this->renderTwigComponent('component_a', [
                    'propA' => 'prop a value',
                    'propB' => 'prop b value',
                ]),
            ],
        );

        $this->assertStringContainsString('<p>some content</p>', $rendered);
        $this->assertStringContainsString('<p>some slot1 content</p>', $rendered);
        $this->assertStringContainsString('propA: prop a value', $rendered);
        $this->assertStringContainsString('propB: prop b value', $rendered);
        $this->assertStringContainsString('service: service a value', $rendered);
    }

    public static function componentANameProvider(): iterable
    {
        yield ['component_a'];
        yield [ComponentA::class];
    }

    public static function withSlotsNameProvider(): iterable
    {
        yield ['WithSlots'];
        yield [WithSlots::class];
    }
}
