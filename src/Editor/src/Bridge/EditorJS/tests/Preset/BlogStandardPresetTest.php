<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Tests\Preset;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\EditorJS\Config\EditorJSConfig;
use Symfony\UX\Editor\Bridge\EditorJS\Preset\BlogStandardPreset;

final class BlogStandardPresetTest extends TestCase
{
    public function testBuildsEditorJSConfig(): void
    {
        $cfg = (new BlogStandardPreset())->build();
        self::assertInstanceOf(EditorJSConfig::class, $cfg);
        self::assertSame('editorjs', $cfg->getBridgeId());

        foreach (['paragraph', 'header', 'list', 'image', 'quote'] as $tool) {
            self::assertArrayHasKey($tool, $cfg->tools);
        }
    }
}
