<?php

namespace Symfony\UX\QuillJs\Tests\DTO\Fields\Inline;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\BoldField;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\Fields\InlineField\BoldField
 */
final class BoldInlineFieldTest extends TestCase
{
    /**
     * @covers ::getOption
     */
    public function testGetOption(): void
    {
        $field = new BoldField();

        $result = $field->getOption();

        $this->assertEquals('bold', $result);
    }
}
