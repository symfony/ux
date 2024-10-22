<?php

namespace Symfony\UX\QuillJs\Tests\DTO\Fields\Block;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\SizeField;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\Fields\BlockField\SizeField
 */
final class SizeFieldTest extends TestCase
{
    /**
     * @covers ::getOption
     */
    public function testGetOptionWithTrueValues(): void
    {
        $field = new SizeField(SizeField::SIZE_FIELD_OPTION_SMALL);
        $result = $field->getOption();
        $expectedResult = ['size' => ['small']];

        $this->assertEquals($expectedResult, $result);

        $field = new SizeField(
            SizeField::SIZE_FIELD_OPTION_SMALL,
            SizeField::SIZE_FIELD_OPTION_LARGE,
            SizeField::SIZE_FIELD_OPTION_HUGE,
        );
        $result = $field->getOption();
        $expectedResult = ['size' => ['small', 'large', 'huge']];

        $this->assertEquals($expectedResult, $result);

        $field = new SizeField(SizeField::SIZE_FIELD_OPTION_NORMAL);
        $result = $field->getOption();
        $expectedResult = ['size' => [false]];

        $this->assertEquals($expectedResult, $result);
    }
}
