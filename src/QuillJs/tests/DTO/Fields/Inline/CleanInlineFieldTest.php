<?php

namespace Symfony\UX\QuillJs\Tests\DTO\Fields\Inline;

use PHPUnit\Framework\TestCase;
use Symfony\UX\QuillJs\DTO\Fields\InlineField\CleanField;

/**
 * @coversDefaultClass \Symfony\UX\QuillJs\DTO\Fields\InlineField\CleanField
 */
final class CleanInlineFieldTest extends TestCase
{
    /**
     * @covers ::getOption
     */
    public function testGetOption(): void
    {
        $field = new CleanField();

        $result = $field->getOption();

        $this->assertEquals('clean', $result);
    }
}
