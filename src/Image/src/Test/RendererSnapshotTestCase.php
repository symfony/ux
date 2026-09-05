<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\Drivers\TextDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\UX\Image\Provider\ProviderInterface;
use Symfony\UX\Image\Renderer\ImageRenderer;
use Symfony\UX\Image\Renderer\LayoutResolver;
use Symfony\UX\Image\Renderer\RenderedImage;
use Symfony\UX\Image\Renderer\RenderOptions;

/**
 * A test case to ease snapshot-testing a provider's rendered URL matrix.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract class RendererSnapshotTestCase extends TestCase
{
    use MatchesSnapshots;

    /**
     * @return iterable<string, array{0: ProviderInterface, 1: RenderOptions}>
     */
    abstract public static function provideOptions(): iterable;

    #[DataProvider('provideOptions')]
    public function testRenderedUrls(ProviderInterface $provider, RenderOptions $options)
    {
        $rendered = new ImageRenderer($provider, new LayoutResolver())->render('/hero.jpg', 'Hero', $options);

        $this->assertMatchesSnapshot($this->format($rendered), new TextDriver());
    }

    private function format(RenderedImage $rendered): string
    {
        $lines = ['src:', $rendered->imgAttributes['src'], '', 'srcset:'];

        foreach (explode(', ', $rendered->imgAttributes['srcset']) as $candidate) {
            $lines[] = $candidate;
        }

        foreach ($rendered->sources as $source) {
            $lines[] = '';
            $lines[] = \sprintf('source (%s):', $source['type']);
            foreach (explode(', ', $source['srcset']) as $candidate) {
                $lines[] = $candidate;
            }
        }

        return implode("\n", $lines);
    }
}
