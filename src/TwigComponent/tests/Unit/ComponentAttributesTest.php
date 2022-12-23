<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\WebpackEncoreBundle\Dto\AbstractStimulusDto;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentAttributesTest extends TestCase
{
    public function testCanConvertToString(): void
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'style' => 'color:black;',
            'value' => '',
            'autofocus' => null,
        ]);

        $this->assertSame(' class="foo" style="color:black;" value="" autofocus', (string) $attributes);
    }

    public function testCanSetDefaults(): void
    {
        $attributes = new ComponentAttributes(['class' => 'foo', 'style' => 'color:black;']);

        $this->assertSame(
            ['class' => 'bar foo', 'style' => 'color:black;'],
            $attributes->defaults(['class' => 'bar', 'style' => 'font-size: 10;'])->all()
        );
        $this->assertSame(
            ' class="bar foo" style="color:black;"',
            (string) $attributes->defaults(['class' => 'bar', 'style' => 'font-size: 10;'])
        );

        $this->assertSame(['class' => 'foo'], (new ComponentAttributes([]))->defaults(['class' => 'foo'])->all());
    }

    public function testCanGetOnly(): void
    {
        $attributes = new ComponentAttributes(['class' => 'foo', 'style' => 'color:black;']);

        $this->assertSame(['class' => 'foo'], $attributes->only('class')->all());
    }

    public function testCanGetWithout(): void
    {
        $attributes = new ComponentAttributes(['class' => 'foo', 'style' => 'color:black;']);

        $this->assertSame(['class' => 'foo'], $attributes->without('style')->all());
    }

    public function testCanAppendStimulusController(): void
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'data-controller' => 'live',
            'data-live-data-value' => '{}',
        ]);

        $controllerDto = $this->createMock(AbstractStimulusDto::class);
        $controllerDto->expects(self::once())
            ->method('toArray')
            ->willReturn([
                'data-controller' => 'foo bar',
                'data-foo-name-value' => 'ryan',
            ]);

        $attributes = $attributes->append($controllerDto);

        $this->assertEquals([
            'class' => 'foo',
            'data-controller' => 'live foo bar',
            'data-live-data-value' => '{}',
            'data-foo-name-value' => 'ryan',
        ], $attributes->all());
    }

    public function testCanAppendArrayOfAttributes(): void
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'data-keep' => 'keep-it',
        ]);

        $attributes = $attributes->append(['data-add' => 'add-it', 'class' => 'bar']);

        $this->assertEquals([
            'class' => 'foo bar',
            'data-keep' => 'keep-it',
            'data-add' => 'add-it',
        ], $attributes->all());
    }

    public function testCanPrependStimulusController(): void
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'data-controller' => 'live',
            'data-live-data-value' => '{}',
        ]);

        $controllerDto = $this->createMock(AbstractStimulusDto::class);
        $controllerDto->expects(self::once())
            ->method('toArray')
            ->willReturn([
                'data-controller' => 'foo bar',
                'data-foo-name-value' => 'ryan',
            ]);

        $attributes = $attributes->prepend($controllerDto);

        $this->assertEquals([
            'class' => 'foo',
            'data-controller' => 'foo bar live',
            'data-live-data-value' => '{}',
            'data-foo-name-value' => 'ryan',
        ], $attributes->all());
    }

    public function testCanPrependArrayOfAttributes(): void
    {
        $attributes = new ComponentAttributes([
            'class' => 'foo',
            'data-keep' => 'keep-it',
        ]);

        $attributes = $attributes->prepend(['data-add' => 'add-it', 'class' => 'bar']);

        $this->assertEquals([
            'class' => 'bar foo',
            'data-keep' => 'keep-it',
            'data-add' => 'add-it',
        ], $attributes->all());
    }
}
