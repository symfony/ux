<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\CKEditor\Tests\Preset;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
use Symfony\UX\Editor\Bridge\CKEditor\Preset\WysiwygFullPreset;

final class WysiwygFullPresetTest extends TestCase
{
    public function testBuildsRichConfig(): void
    {
        $cfg = (new WysiwygFullPreset())->build();
        self::assertInstanceOf(CKEditorConfig::class, $cfg);
        $toolbar = $cfg->getCommon()->toolbar;
        foreach (['heading', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'] as $tool) {
            self::assertContains($tool, $toolbar);
        }
        self::assertNotNull($cfg->heading);
        self::assertSame('GPL', $cfg->licenseKey);
    }
}
