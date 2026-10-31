<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\PropertyName;

final class PropertyNameTest extends TestCase
{
    public static function provideProperties(): iterable
    {
        yield 'lowercase' => ['color', 'color'];
        yield 'camel case' => ['backgroundColor', 'background-color'];
        yield 'vendor prefix' => ['WebkitTextFillColor', '-webkit-text-fill-color'];
        yield 'microsoft prefix' => ['msOverflowStyle', '-ms-overflow-style'];
        yield 'custom property' => ['--brandColor', '--brandColor'];
    }

    #[DataProvider('provideProperties')]
    public function testToCss(string $property, string $expected): void
    {
        $this->assertSame($expected, PropertyName::toCss($property));
    }
}
