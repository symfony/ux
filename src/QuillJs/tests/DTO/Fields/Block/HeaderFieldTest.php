<?php

namespace Symfony\UX\QuillJs\Tests\DTO\Fields\Block;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\HeaderField;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\Fields\BlockField\HeaderField
 */
final class HeaderFieldTest extends TestCase
{
    /**
     * @covers ::getOption
     */
    public function testGetOptionWithTrueValues(): void
    {
        $field = new HeaderField();
        $result = $field->getOption();
        $expectedResult = ['header' => 1];

        $this->assertEquals($expectedResult, $result);

        $field = new HeaderField(HeaderField::HEADER_OPTION_2);
        $result = $field->getOption();
        $expectedResult = ['header' => 2];

        $this->assertEquals($expectedResult, $result);
    }
}
