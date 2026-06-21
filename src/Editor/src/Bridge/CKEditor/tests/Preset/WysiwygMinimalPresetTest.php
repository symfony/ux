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
use Symfony\UX\Editor\Bridge\CKEditor\Preset\WysiwygMinimalPreset;

final class WysiwygMinimalPresetTest extends TestCase
{
    public function testBuilds()
    {
        $cfg = new WysiwygMinimalPreset()->build();
        self::assertInstanceOf(CKEditorConfig::class, $cfg);
        self::assertSame('ckeditor', $cfg->getBridgeId());
        self::assertSame(['bold', 'italic', 'link'], $cfg->getCommon()->toolbar);
        self::assertSame('GPL', $cfg->licenseKey);
    }
}
