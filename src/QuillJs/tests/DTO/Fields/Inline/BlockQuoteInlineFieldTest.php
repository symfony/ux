<?php

namespace Symfony\UX\QuillJs\Tests\DTO\Fields\Inline;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\BlockQuoteField;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\Fields\InlineField\BlockQuoteField
 */
final class BlockQuoteInlineFieldTest extends TestCase
{
    /**
     * @covers ::getOption
     */
    public function testGetOption(): void
    {
        $field = new BlockQuoteField();
        $result = $field->getOption();

        $this->assertEquals('blockquote', $result);
    }
}
