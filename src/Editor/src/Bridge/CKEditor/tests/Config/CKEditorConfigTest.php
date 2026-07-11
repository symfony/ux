<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\CKEditor\Tests\Config;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\WysiwygCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

final class CKEditorConfigTest extends TestCase
{
    public function testBridgeIdAndCapabilities()
    {
        $cfg = new CKEditorConfig();
        self::assertSame('ckeditor', $cfg->getBridgeId());
        self::assertEquals(WysiwygCapabilities::default(), $cfg->getCapabilities());
    }

    public function testTranslateCommonAndOwn()
    {
        $cfg = new CKEditorConfig(
            common: new CommonOptions(toolbar: ['bold', 'italic', 'link'], placeholder: 'Write…', language: 'fr'),
            extraPlugins: ['SourceEditing'],
            removePlugins: ['Markdown'],
            heading: ['options' => [['model' => 'paragraph', 'title' => 'P']]],
            image: ['toolbar' => ['imageTextAlternative']],
            link: ['decorators' => ['openInNewTab' => ['mode' => 'manual']]],
            licenseKey: 'GPL',
        );
        $n = $cfg->toNative();
        self::assertSame(['items' => ['bold', 'italic', 'link']], $n['toolbar']);
        self::assertSame('Write…', $n['placeholder']);
        self::assertSame('fr', $n['language']);
        self::assertSame(['SourceEditing'], $n['extraPlugins']);
        self::assertSame(['Markdown'], $n['removePlugins']);
        self::assertArrayHasKey('heading', $n);
        self::assertArrayHasKey('image', $n);
        self::assertArrayHasKey('link', $n);
        self::assertSame('GPL', $n['licenseKey']);
    }

    public function testToolbarOmittedWhenNotSet()
    {
        self::assertArrayNotHasKey('toolbar', new CKEditorConfig()->toNative());
    }

    public function testNativeOverridesWinLast()
    {
        $cfg = new CKEditorConfig(
            common: new CommonOptions(language: 'fr'),
            nativeOverrides: ['language' => 'en', 'ui' => ['poweredBy' => ['forceVisible' => false]]],
        );
        $n = $cfg->toNative();
        self::assertSame('en', $n['language']);
        self::assertFalse($n['ui']['poweredBy']['forceVisible']);
    }
}
