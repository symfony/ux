<?php

namespace DTO\Fields\Block;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\FontField;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\Fields\BlockField\FontField
 */
final class FontFieldTest extends TestCase
{
    /**
     * @covers ::getOption
     */
    public function testGetOptionWithTrueValues(): void
    {
        $field = new FontField();

        $result = $field->getOption();

        $expectedResult = [
            'font' => [
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @covers ::getOption
     */
    public function testGetOptionWithOneValue(): void
    {
        $field = new FontField(FontField::FONT_OPTION_SERIF);

        $result = $field->getOption();

        $expectedResult = [
            'font' => ['serif'],
        ];

        $this->assertEquals($expectedResult, $result);
    }
}
