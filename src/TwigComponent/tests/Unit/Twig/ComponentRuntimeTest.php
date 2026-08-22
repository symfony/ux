<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\Twig\ComponentRuntime;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;

class ComponentRuntimeTest extends TestCase
{
    public function testCustomRendererReceivesPlainAttributeValues()
    {
        if (!interface_exists(AttributeValueInterface::class)) {
            $this->markTestSkipped('Requires twig/html-extra >= 3.24.');
        }

        $customRenderer = new class {
            public ?array $receivedProps = null;

            public function render(array $props): string
            {
                $this->receivedProps = $props;

                return '<svg></svg>';
            }
        };

        $container = new Container();
        $container->set('ux:icon', $customRenderer);

        $runtime = new ComponentRuntime(
            $this->createMock(ComponentRendererInterface::class),
            $container,
            new ComponentStack(),
        );

        $this->assertSame('<svg></svg>', $runtime->render('UX:Icon', [
            'name' => 'lucide:user',
            'class' => 'size-4',
            'data-action' => $this->createTypedValue('click->menu#toggle'),
            'aria-hidden' => $this->createTypedValue(null),
            'title' => new class implements \Stringable {
                public function __toString(): string
                {
                    return 'User';
                }
            },
            'disabled' => true,
        ]));

        $this->assertSame([
            'name' => 'lucide:user',
            'class' => 'size-4',
            'data-action' => 'click->menu#toggle',
            'aria-hidden' => false,
            'title' => 'User',
            'disabled' => true,
        ], $customRenderer->receivedProps);
    }

    private function createTypedValue(?string $value): AttributeValueInterface
    {
        return new class($value) implements AttributeValueInterface {
            public function __construct(private readonly ?string $value)
            {
            }

            public function getValue(): ?string
            {
                return $this->value;
            }
        };
    }
}
