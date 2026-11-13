<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

final class StreamComponentsTest extends KernelTestCase
{
    /**
     * @param array<string, mixed> $context
     */
    #[DataProvider('provideComponents')]
    public function testRenderStreamComponent(string $template, array $context, string $expectedResult): void
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $this->assertSame($expectedResult, $twig->createTemplate($template)->render($context));
    }

    /**
     * @return iterable<array{0: string, 1: array<string, mixed>, 2: string}>
     */
    public static function provideComponents(): iterable
    {
        foreach (['append', 'prepend', 'before', 'after', 'update', 'replace'] as $action) {
            $component = 'Turbo:Stream:'.ucfirst($action);

            yield $action.' with a CSS selector' => [
                \sprintf('<twig:%1$s target="#task_42"><li>My task</li></twig:%1$s>', $component),
                [],
                \sprintf(<<<'HTML'
                    <turbo-stream action="%s" targets="#task_42">
                        <template><li>My task</li></template>
                    </turbo-stream>

                    HTML, $action),
            ];

            yield $action.' with an id' => [
                \sprintf('<twig:%1$s id="{{ id }}"><li>My task</li></twig:%1$s>', $component),
                ['id' => '3f0c252d-88d4-449e-b5a9-dd793e393354'],
                \sprintf(<<<'HTML'
                    <turbo-stream action="%s" target="3f0c252d-88d4-449e-b5a9-dd793e393354">
                        <template><li>My task</li></template>
                    </turbo-stream>

                    HTML, $action),
            ];

            yield $action.' with content reading the id variable of the outer template' => [
                \sprintf('<twig:%1$s target="#task_{{ id }}"><li>Task {{ id }}</li></twig:%1$s>', $component),
                ['id' => 42],
                \sprintf(<<<'HTML'
                    <turbo-stream action="%s" targets="#task_42">
                        <template><li>Task 42</li></template>
                    </turbo-stream>

                    HTML, $action),
            ];
        }

        foreach (['update', 'replace'] as $action) {
            $component = 'Turbo:Stream:'.ucfirst($action);

            yield $action.' with an id and morphing' => [
                \sprintf('<twig:%1$s id="42" morph><li>My task</li></twig:%1$s>', $component),
                [],
                \sprintf(<<<'HTML'
                    <turbo-stream action="%s" target="42" method="morph">
                        <template><li>My task</li></template>
                    </turbo-stream>

                    HTML, $action),
            ];
        }

        yield 'remove with a CSS selector' => [
            '<twig:Turbo:Stream:Remove target="#task_42" />',
            [],
            '<turbo-stream action="remove" targets="#task_42"></turbo-stream>'."\n",
        ];

        yield 'remove with an id' => [
            '<twig:Turbo:Stream:Remove id="42" />',
            [],
            '<turbo-stream action="remove" target="42"></turbo-stream>'."\n",
        ];

        yield 'id of the outer template is not used as the target' => [
            '<twig:Turbo:Stream:Remove target="#task_{{ id }}" />',
            ['id' => '42'],
            '<turbo-stream action="remove" targets="#task_42"></turbo-stream>'."\n",
        ];

        yield 'escaped id' => [
            '<twig:Turbo:Stream:Remove id="{{ id }}" />',
            ['id' => '"><script>alert(1)</script>'],
            '<turbo-stream action="remove" target="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"></turbo-stream>'."\n",
        ];
    }
}
