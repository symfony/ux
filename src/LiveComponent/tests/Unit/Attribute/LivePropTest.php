<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\LiveComponentInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LivePropTest extends TestCase
{
    /**
     * @test
     */
    public function hydrate_with_method(): void
    {
        $this->assertSame('someMethod', (new LiveProp(['hydrateWith' => 'someMethod']))->hydrateMethod());
        $this->assertSame('someMethod', (new LiveProp(['hydrateWith' => 'someMethod()']))->hydrateMethod());
    }

    /**
     * @test
     */
    public function dehydrate_with_method(): void
    {
        $this->assertSame('someMethod', (new LiveProp(['dehydrateWith' => 'someMethod']))->dehydrateMethod());
        $this->assertSame('someMethod', (new LiveProp(['dehydrateWith' => 'someMethod()']))->dehydrateMethod());
    }

    /**
     * @test
     */
    public function can_call_calculate_field_name_as_string(): void
    {
        $component = new class() implements LiveComponentInterface {
            public static function getComponentName(): string
            {
                return 'name';
            }
        };

        $this->assertSame('field', (new LiveProp(['fieldName' => 'field']))->calculateFieldName($component, 'fallback'));
    }

    /**
     * @test
     */
    public function can_call_calculate_field_name_as_method(): void
    {
        $component = new class() implements LiveComponentInterface {
            public static function getComponentName(): string
            {
                return 'name';
            }

            public function fieldName(): string
            {
                return 'foo';
            }
        };

        $this->assertSame('foo', (new LiveProp(['fieldName' => 'fieldName()']))->calculateFieldName($component, 'fallback'));
    }

    /**
     * @test
     */
    public function can_call_calculate_field_name_when_not_set(): void
    {
        $component = new class() implements LiveComponentInterface {
            public static function getComponentName(): string
            {
                return 'name';
            }
        };

        $this->assertSame('fallback', (new LiveProp([]))->calculateFieldName($component, 'fallback'));
    }
}
