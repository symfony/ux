<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Wysiwyg;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygConfig;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\WysiwygCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

final class AbstractWysiwygConfigTest extends TestCase
{
    public function testDefaultTranslateCommon(): void
    {
        $cfg = new class(new CommonOptions(placeholder: 'Write…', readOnly: true, language: 'fr', plugins: ['link'])) extends AbstractWysiwygConfig {
            public function getBridgeId(): string { return 'fake'; }
        };
        $n = $cfg->toNative();
        self::assertSame('Write…', $n['placeholder']);
        self::assertTrue($n['readOnly']);
        self::assertSame('fr', $n['language']);
        self::assertSame(['link'], $n['plugins']);
    }

    public function testCapabilitiesDefaultToWysiwyg(): void
    {
        $cfg = new class extends AbstractWysiwygConfig {
            public function getBridgeId(): string { return 'fake'; }
        };
        self::assertEquals(WysiwygCapabilities::default(), $cfg->getCapabilities());
        self::assertSame(['html'], $cfg->getCapabilities()->supportedFormats);
    }

    public function testSubclassCanExtendTranslateOwn(): void
    {
        $cfg = new class(new CommonOptions(placeholder: 'X')) extends AbstractWysiwygConfig {
            public function getBridgeId(): string { return 'extra'; }
            protected function translateOwn(): array { return ['custom' => 'value']; }
        };
        $n = $cfg->toNative();
        self::assertSame('X', $n['placeholder']);
        self::assertSame('value', $n['custom']);
    }
}
