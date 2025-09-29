<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\Drivers\TextDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\UX\Map\Elements;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Renderer\RendererInterface;

/**
 * A test case to ease testing a renderer.
 */
abstract class RendererTestCase extends TestCase
{
    use MatchesSnapshots;

    /**
     * @return iterable<array{renderer: RendererInterface, map: Map, attributes: array<mixed>}>
     */
    abstract public static function provideTestRenderMap(): iterable;

    /**
     * @dataProvider provideTestRenderMap
     */
    #[DataProvider('provideTestRenderMap')]
    public function testRenderMap(RendererInterface $renderer, Map $map, array $attributes = []): void
    {
        $rendered = $renderer->renderMap($map, $attributes);
        $rendered = $this->prettify($rendered);

        $this->assertElementsHaveComputedId($rendered);
        $this->assertMatchesSnapshot($rendered, new TextDriver());
    }

    private function prettify(string $html): string
    {
        $html = preg_replace('/ ([a-zA-Z-]+=")/', "\n  $1", $html);
        $html = str_replace('">', "\"\n>", $html);
        $html = '<!-- This HTML has been prettified for testing purposes, and may not represent the actual HTML output.
     Run "php vendor/bin/phpunit -d --update-snapshots" to update the snapshot. -->'."\n".$html;

        return $html;
    }

    private function assertElementsHaveComputedId(string $html): void
    {
        // Extract the `Elements` properties from the Map class
        static $elementsProperties = null;
        if (null === $elementsProperties) {
            $elementsProperties = [];
            $reflMap = new \ReflectionClass(Map::class);

            foreach ($reflMap->getProperties() as $prop) {
                if (is_a($prop->getType()->getName(), Elements::class, true)) {
                    $elementsProperties[] = $prop->getName();
                }
            }
        }

        // Parse the rendered HTML and extract `data-<element>-value` attributes
        /** @var array<string, string> $htmlAttributes */
        $htmlAttributes = [];
        foreach ($elementsProperties as $property) {
            $matchesResult = preg_match(\sprintf('/data-symfony--ux-[a-z-]+-map-%s-value="([^"]+)"/', preg_quote($property, '/')), $html, $matches);
            if (false === $matchesResult) {
                throw new \LogicException(\sprintf('Failed to parse the rendered HTML for property "%s".', $property));
            } elseif (0 === $matchesResult) {
                throw new \LogicException(\sprintf('It looks like the property "%s" is missing from "Map::toArray()" normalization.', $property));
            } else {
                $htmlAttributes[$property] = $matches[1];
            }
        }

        // Check that each property has a computed "@id" attribute
        foreach ($htmlAttributes as $property => $value) {
            if ('[]' === $value) {
                continue;
            }

            if (!str_contains($value, '@id') || str_contains($value, '&quot;@id&quot;:null')) {
                throw new \LogicException(\sprintf('It looks like the "AbstractRendered::getMapAttributes()" has not been updated to compute "@id" attribute of "%s" property.', $property));
            }
        }
    }
}
