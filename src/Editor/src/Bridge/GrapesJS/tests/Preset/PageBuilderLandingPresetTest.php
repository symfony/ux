<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS\Tests\Preset;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\GrapesJS\Config\GrapesJSConfig;
use Symfony\UX\Editor\Bridge\GrapesJS\Preset\PageBuilderLandingPreset;

final class PageBuilderLandingPresetTest extends TestCase
{
    public function testBuilds()
    {
        $cfg = new PageBuilderLandingPreset()->build();
        self::assertInstanceOf(GrapesJSConfig::class, $cfg);
        self::assertSame('grapesjs', $cfg->getBridgeId());
        self::assertNotEmpty($cfg->blocks);
        self::assertNotEmpty($cfg->deviceManager);
    }

    public function testIncludesCommonBlocks()
    {
        $cfg = new PageBuilderLandingPreset()->build();
        $ids = array_map(static fn ($b) => $b['id'], $cfg->blocks);
        foreach (['hero', 'section', 'text', 'image'] as $expected) {
            self::assertContains($expected, $ids);
        }
    }
}
