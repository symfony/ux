<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS\Tests\Config;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Page\PageCapabilities;
use Symfony\UX\Editor\Bridge\GrapesJS\Config\GrapesJSConfig;
use Symfony\UX\Editor\Config\CommonOptions;

final class GrapesJSConfigTest extends TestCase
{
    public function testBridgeIdAndCapabilities()
    {
        $cfg = new GrapesJSConfig();
        self::assertSame('grapesjs', $cfg->getBridgeId());
        self::assertEquals(PageCapabilities::default(), $cfg->getCapabilities());
    }

    public function testDefaultStorageManagerNone()
    {
        self::assertSame(['type' => 'none'], new GrapesJSConfig()->toNative()['storageManager']);
    }

    public function testTranslateOwn()
    {
        $cfg = new GrapesJSConfig(
            common: new CommonOptions(theme: 'dark', language: 'fr'),
            components: [['type' => 'image', 'tagName' => 'img']],
            blocks: [['id' => 'h1-block', 'label' => 'Heading', 'content' => '<h1>Heading</h1>']],
            storageManager: ['type' => 'local', 'autosave' => true],
            deviceManager: ['devices' => [['name' => 'Desktop', 'width' => '']]],
            canvasCss: 'body{margin:0}',
        );
        $n = $cfg->toNative();
        self::assertSame('dark', $n['theme']);
        self::assertSame('fr', $n['language']);
        self::assertCount(1, $n['components']);
        self::assertCount(1, $n['blocks']);
        self::assertSame(['type' => 'local', 'autosave' => true], $n['storageManager']);
        self::assertArrayHasKey('deviceManager', $n);
        self::assertSame('body{margin:0}', $n['canvas']['styles'][0]);
    }

    public function testNativeOverridesWinLast()
    {
        $cfg = new GrapesJSConfig(
            storageManager: ['type' => 'local'],
            nativeOverrides: ['storageManager' => ['type' => 'remote']],
        );
        self::assertSame(['type' => 'remote'], $cfg->toNative()['storageManager']);
    }
}
