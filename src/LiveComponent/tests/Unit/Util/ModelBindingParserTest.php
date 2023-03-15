<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Util\ModelBindingParser;

final class ModelBindingParserTest extends TestCase
{
    /**
     * @dataProvider getModelStringTests
     */
    public function testParseAllValidStrings(string $input, array $expectedBindings): void
    {
        $parser = new ModelBindingParser();
        $this->assertEquals($expectedBindings, $parser->parse($input));
    }

    public function getModelStringTests(): \Generator
    {
        yield 'empty_string' => ['', []];

        yield 'valid_but_empty_second_mode' => ['foo:bar ', [
            ['parent' => 'foo', 'child' => 'bar'],
        ]];

        yield 'valid_without_colon_uses_value_default' => ['foo ', [
            ['parent' => 'foo', 'child' => 'value'],
        ]];

        yield 'multiple_spaces_between_models' => ['foo    bar:baz', [
            ['parent' => 'foo', 'child' => 'value'],
            ['parent' => 'bar', 'child' => 'baz'],
        ]];
    }

    public function testParseThrowsExceptionWithMultipleColons(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "foo:bar:baz" given for "data-model"');

        $parser = new ModelBindingParser();
        $parser->parse('foo:bar:baz');
    }
}
