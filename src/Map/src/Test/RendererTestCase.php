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
use Spatie\Snapshots\MatchesSnapshots;
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

        $this->assertMatchesSnapshot($rendered);
    }

    private function prettify(string $html): string
    {
        $html = preg_replace('/ ([a-zA-Z-]+=")/', "\n  $1", $html);
        $html = str_replace('">', "\"\n>", $html);
        $html = '<!-- This HTML has been prettified for testing purposes, and may not represent the actual HTML output.
     Run "php vendor/bin/phpunit -d --update-snapshots" to update the snapshot. -->'."\n".$html;

        return $html;
    }
}
