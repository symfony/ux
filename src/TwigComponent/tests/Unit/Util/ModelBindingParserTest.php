<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\Util\ModelBindingParser;

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
            ['child' => 'foo', 'parent' => 'bar'],
        ]];

        yield 'valid_without_colon_uses_value_default' => ['foo ', [
            ['child' => 'value', 'parent' => 'foo'],
        ]];

        yield 'multiple_spaces_between_models' => ['foo    bar:baz', [
            ['child' => 'value', 'parent' => 'foo'],
            ['child' => 'bar', 'parent' => 'baz'],
        ]];
    }

    public function testParseThrowsExceptionWithMutipleColons(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "foo:bar:baz" given for "data-model"');

        $parser = new ModelBindingParser();
        $parser->parse('foo:bar:baz');
    }
}
