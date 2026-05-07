<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Block;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Block\BlockRendererInterface;
use Symfony\UX\Editor\Bridge\Format\Block\BlockRendererRegistry;

final class BlockRendererRegistryTest extends TestCase
{
    public function testGetByType(): void
    {
        $r = new class implements BlockRendererInterface {
            public function getBlockType(): string { return 'header'; }
            public function render(array $blockData, array $blockMeta = []): string
            {
                return '<h>'.($blockData['text'] ?? '').'</h>';
            }
        };
        $reg = new BlockRendererRegistry([$r]);
        self::assertSame($r, $reg->get('header'));
        self::assertNull($reg->get('unknown'));
    }

    public function testLastWriterWins(): void
    {
        $a = new class implements BlockRendererInterface {
            public function getBlockType(): string { return 'p'; }
            public function render(array $blockData, array $blockMeta = []): string { return 'A'; }
        };
        $b = new class implements BlockRendererInterface {
            public function getBlockType(): string { return 'p'; }
            public function render(array $blockData, array $blockMeta = []): string { return 'B'; }
        };
        self::assertSame('B', (new BlockRendererRegistry([$a, $b]))->get('p')->render([]));
    }
}
