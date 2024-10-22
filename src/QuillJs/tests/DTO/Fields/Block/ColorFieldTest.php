<?php

namespace Symfony\UX\QuillJs\Tests\DTO\Fields\Block;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\ColorField;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\Fields\BlockField\ColorField
 */
final class ColorFieldTest extends TestCase
{
    /**
     * @covers ::getOption
     */
    public function testGetOptionWithTrueValues(): void
    {
        $field = new ColorField(
            'green', 'red', 'yellow'
        );

        $result = $field->getOption();

        $expectedResult = [
            'color' => [
                'green', 'red', 'yellow',
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }
}
