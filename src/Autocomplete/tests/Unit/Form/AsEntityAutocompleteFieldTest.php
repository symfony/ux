<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Unit\Form;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Tests\Fixtures\Form\ProductType;

class AsEntityAutocompleteFieldTest extends TestCase
{
    /**
     * @dataProvider provideClassNames
     */
    public function testShortName(string $shortName, string $className): void
    {
        $this->assertEquals($shortName, AsEntityAutocompleteField::shortName($className));
    }

    /**
     * @return iterable<{string, string}>
     */
    public static function provideClassNames(): iterable
    {
        yield from [
            ['as_entity_autocomplete_field', AsEntityAutocompleteField::class],
            ['product_type', ProductType::class],
            ['bar', 'Bar'],
            ['foo_bar', 'FooBar'],
            ['foo_bar', 'Foo\FooBar'],
            ['foo_bar', 'Foo\Bar\FooBar'],
        ];
    }
}
