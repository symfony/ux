<?php

namespace Symfony\UX\QuillJs\Tests\DTO;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\ColorField;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\HeaderGroupField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\BoldField;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\ItalicField;
use Symfony\UX\QuillJs\DTO\QuillGroup;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\QuillGroup
 */
class QuillGroupTest extends TestCase
{
    /**
     * @covers ::build
     */
    public function testBuild(): void
    {
        $boldInlineField = new BoldField();
        $italicInlineField = new ItalicField();
        $colorBlockField = new ColorField('green');
        $headerBlockField = new HeaderGroupField(HeaderGroupField::HEADER_OPTION_1, HeaderGroupField::HEADER_OPTION_3);

        $result = QuillGroup::build($boldInlineField, $italicInlineField, $colorBlockField, $headerBlockField);

        $expectedResult = [
            'bold',
            'italic',
            ['color' => ['green']],
            ['header' => [1, 3]],
        ];

        $this->assertEquals($expectedResult, $result);
    }
}
