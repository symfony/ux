<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Doc;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Doc\InlineExamplesConverter;

class InlineExamplesConverterTest extends TestCase
{
    public function testConvertInlinesRunnableExampleWithPreviewAndOptions()
    {
        $readme = "## Examples\n\n::: example Basic {\"height\": \"150px\"}\n";
        $out = InlineExamplesConverter::convert($readme, static fn (string $name): string => "<twig:Avatar name=\"$name\" />");

        $this->assertStringContainsString('```twig {"preview":true,"height":"150px"}', $out);
        $this->assertStringContainsString('<twig:Avatar name="Basic" />', $out);
        $this->assertStringNotContainsString('::: example', $out);
    }

    public function testConvertKeepsUsageStaticWithoutPreview()
    {
        $readme = "## Usage\n\n::: example Usage\n";
        $out = InlineExamplesConverter::convert($readme, static fn (string $name): string => '<twig:Avatar />');

        $this->assertStringContainsString("```twig\n<twig:Avatar />\n```", $out);
        $this->assertStringNotContainsString('preview', $out);
    }
}
