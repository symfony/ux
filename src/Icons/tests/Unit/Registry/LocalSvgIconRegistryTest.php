<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Registry\LocalSvgIconRegistry;
use Symfony\UX\Icons\Svg\Icon;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LocalSvgIconRegistryTest extends TestCase
{
    /**
     * @dataProvider validSvgProvider
     */
    public function testValidSvgs(string $name, array $expectedAttributes, string $expectedContent): void
    {
        $icon = $this->registry()->get($name);
        $this->assertInstanceOf(Icon::class, $icon);
        $this->assertSame($expectedContent, $icon->getInnerSvg());
        $this->assertSame($expectedAttributes, $icon->getAttributes());
    }

    public static function validSvgProvider(): iterable
    {
        yield [
            'valid1',
            ['viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'class' => 'w-6 h-6'],
            '<path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"></path>',
        ];

        yield [
            'valid2',
            ['viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'class' => 'w-6 h-6'],
            '<path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"></path>',
        ];

        yield [
            'valid3',
            ['viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'class' => 'w-6 h-6'],
            '<path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"></path>',
        ];

        yield [
            'valid4',
            ['viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'class' => 'w-6 h-6'],
            '<path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"></path><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"></path>',
        ];

        yield [
            'valid5',
            ['viewBox' => '0 0 24 24', 'fill' => 'currentColor', 'class' => 'w-6 h-6'],
            '<path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"></path><g><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd"></path></g>',
        ];
    }

    /**
     * @dataProvider invalidSvgProvider
     */
    public function testInvalidSvgs(string $name): void
    {
        $this->expectException(\RuntimeException::class);

        $this->registry()->get($name);
    }

    public static function invalidSvgProvider(): iterable
    {
        yield ['invalid1'];
        yield ['invalid2'];
        yield ['invalid3'];
        yield ['invalid4'];
    }

    private function registry(): LocalSvgIconRegistry
    {
        return new LocalSvgIconRegistry(__DIR__.'/../../Fixtures/svg');
    }
}
