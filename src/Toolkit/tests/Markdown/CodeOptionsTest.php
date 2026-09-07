<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Markdown;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Markdown\CodeOptions;

class CodeOptionsTest extends TestCase
{
    public function testFromInfoJsonReadsFilenameAndCollapseClass(): void
    {
        $options = CodeOptions::fromInfoJson('{"filename": "templates/components/Alert.html.twig", "collapseClass": true}');

        $this->assertSame('templates/components/Alert.html.twig', $options?->filename);
        $this->assertTrue($options->collapseClass);
    }

    public function testFromInfoJsonDefaultsToEmptyOptions(): void
    {
        $options = CodeOptions::fromInfoJson('{}');

        $this->assertNull($options?->filename);
        $this->assertFalse($options->collapseClass);
    }

    public function testFromInfoJsonIgnoresANonStringFilename(): void
    {
        $this->assertNull(CodeOptions::fromInfoJson('{"filename": 42}')?->filename);
    }

    public function testFromInfoJsonReturnsNullOnMalformedJson(): void
    {
        $this->assertNull(CodeOptions::fromInfoJson('not json'));
        $this->assertNull(CodeOptions::fromInfoJson('"a string"'));
    }

    public function testToInfoJsonCarriesTheFilenameWithUnescapedSlashes(): void
    {
        $json = new CodeOptions(filename: 'templates/components/Alert.html.twig')->toInfoJson();

        $this->assertSame('{"filename":"templates/components/Alert.html.twig"}', $json);
    }

    public function testToInfoJsonCarriesCollapseClass(): void
    {
        $this->assertSame('{"collapseClass":true}', new CodeOptions(collapseClass: true)->toInfoJson());
    }

    public function testToInfoJsonIsNullWhenThereIsNothingToCarry(): void
    {
        $this->assertNull(new CodeOptions()->toInfoJson());
    }

    public function testInfoJsonRoundTrips(): void
    {
        $options = new CodeOptions(filename: 'assets/controllers/alert_controller.js', collapseClass: true);

        $decoded = CodeOptions::fromInfoJson($options->toInfoJson());

        $this->assertSame($options->filename, $decoded?->filename);
        $this->assertSame($options->collapseClass, $decoded->collapseClass);
    }
}
