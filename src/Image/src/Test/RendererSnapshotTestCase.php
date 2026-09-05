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

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\UX\Image\Renderer\RenderedImage;

/**
 * A test case to ease snapshot-testing a provider's rendered URL matrix.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract class RendererSnapshotTestCase extends TestCase
{
    use MatchesSnapshots;

    protected function format(RenderedImage $rendered): string
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
