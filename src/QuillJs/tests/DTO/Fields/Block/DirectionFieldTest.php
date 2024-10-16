<?php

namespace Symfony\UX\QuillJs\Tests\DTO\Fields\Block;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\BlockField\DirectionField;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\Fields\BlockField\DirectionField
 */
final class DirectionFieldTest extends TestCase
{
    /**
     * @covers ::getOption
     */
    public function testGetOptionWithTrueValues(): void
    {
        $field = new DirectionField('rtl');
        $result = $field->getOption();
        $expectedResult = ['direction' => 'rtl'];

        $this->assertEquals($expectedResult, $result);
    }
}
