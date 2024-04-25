<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\Twig\TemplateNameParser;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class TemplateNameParserTest extends TestCase
{
    /**
     * @dataProvider provideParse
     */
    public function testParse(string $name, string $parsedName): void
    {
        $this->assertSame($parsedName, TemplateNameParser::parse($name));
    }

    public function testParseThrowsExceptionWhenInvalidName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Malformed namespaced template name "@foo" (expecting "@namespace/template_name").');
        TemplateNameParser::parse('@foo');
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function provideParse(): iterable
    {
        yield ['foo', 'foo'];
        yield ['foo/bar', 'foo/bar'];
        yield ['@Admin/foo', '@Admin/foo'];
        yield ['Admin/foo', 'Admin/foo'];
    }
}
