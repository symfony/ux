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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;

final class RenderVariablesTest extends KernelTestCase
{
    public function testEmbeddedComponentVariablesKeepTheirOrderAndPrecedence(): void
    {
        $variables = $this->captureRenderVariables(
            '{% component "component_a" with { propA: "fromProp", propB: "mounted" } %}{% endcomponent %}',
            ['propA' => 'fromContext', 'outer' => 'fromContext'],
        );

        $keys = array_keys($variables);
        $expectedKeys = ['propA', 'outer', PreRenderEvent::EMBEDDED, 'propB', 'attributes'];

        self::assertSame($expectedKeys, array_values(array_intersect($keys, $expectedKeys)));
        self::assertSame('fromProp', $variables['propA']);
        self::assertSame('fromContext', $variables['outer']);
        self::assertTrue($variables[PreRenderEvent::EMBEDDED]);
    }

    public function testIntegerContextKeysAreRenumbered(): void
    {
        $variables = $this->captureRenderVariables(
            '{% component "component_a" with { propA: "a", propB: "b" } %}{% endcomponent %}',
            [7 => 'seven', 9 => 'nine'],
        );

        self::assertSame('seven', $variables[0]);
        self::assertSame('nine', $variables[1]);
        self::assertArrayNotHasKey(7, $variables);
        self::assertArrayNotHasKey(9, $variables);
    }

    public function testNonEmbeddedComponentVariablesKeepTheirOrder(): void
    {
        $variables = $this->captureRenderVariables('{{ component("component_a", { propA: "a", propB: "b" }) }}');

        self::assertSame(['propA', 'propB', 'attributes'], array_keys($variables));
    }

    private function captureRenderVariables(string $template, array $context = []): array
    {
        $variables = null;
        self::getContainer()->get('event_dispatcher')->addListener(
            PreRenderEvent::class,
            static function (PreRenderEvent $event) use (&$variables): void {
                $variables ??= $event->getVariables();
            },
        );

        self::getContainer()->get(Environment::class)->createTemplate($template)->render($context);

        self::assertNotNull($variables, 'The PreRenderEvent was not dispatched.');

        return $variables;
    }
}
