<?php

namespace Symfony\UX\Image\Tests\TestHelper;

trait HtmlTestHelper
{
    protected function parseImageAttributes(string $html): array
    {
        $dom = new \DOMDocument();
        $html = '<!DOCTYPE html><html><body>'.$html.'</body></html>';
        @$dom->loadHTML($html, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);

        $img = $dom->getElementsByTagName('img')->item(0);

        if (!$img) {
            $picture = $dom->getElementsByTagName('picture')->item(0);
            if ($picture) {
                $img = $picture->getElementsByTagName('img')->item(0);
            }
        }

        if (!$img) {
            throw new \RuntimeException('No img element found in HTML');
        }

        $attributes = [];
        foreach ($img->attributes as $attr) {
            $attributes[$attr->name] = $attr->value;
        }

        if (isset($attributes['src'])) {
            $parsedUrl = parse_url($attributes['src']);
            $attributes['src_path'] = $parsedUrl['path'] ?? null;
            $attributes['src_scheme'] = $parsedUrl['scheme'] ?? null;

            if (isset($parsedUrl['query'])) {
                parse_str(html_entity_decode($parsedUrl['query']), $params);
                $attributes['src_params'] = $params;
            }
        }

        return $attributes;
    }

    protected function assertImageAttribute(string $html, string $attribute, string $expected): void
    {
        $attributes = $this->parseImageAttributes($html);
        $this->assertArrayHasKey($attribute, $attributes, "Image is missing attribute '$attribute'");
        $this->assertEquals($expected, $attributes[$attribute], "Image attribute '$attribute' does not match expected value");
    }

    protected function assertImageSrcParam(string $html, string $param, string $expected): void
    {
        $attributes = $this->parseImageAttributes($html);
        $this->assertArrayHasKey('src_params', $attributes, 'Image src has no parameters');
        $this->assertArrayHasKey($param, $attributes['src_params'], "Image src is missing parameter '$param'");
        $this->assertEquals($expected, $attributes['src_params'][$param], "Image src parameter '$param' does not match expected value");
    }

    protected function assertSourceAttribute(string $html, string $attribute, string $expected, int $index = 0): void
    {
        $dom = new \DOMDocument();
        $html = '<!DOCTYPE html><html><body>'.$html.'</body></html>';
        @$dom->loadHTML($html, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);

        $sources = $dom->getElementsByTagName('source');

        if ($sources->length <= $index) {
            throw new \RuntimeException("No source element found at index $index");
        }

        $source = $sources->item($index);
        $this->assertTrue($source->hasAttribute($attribute), "Source is missing attribute '$attribute'");
        $this->assertEquals($expected, $source->getAttribute($attribute), "Source attribute '$attribute' does not match expected value");
    }
}
