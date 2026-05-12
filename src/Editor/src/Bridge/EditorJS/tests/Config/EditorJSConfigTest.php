<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Tests\Config;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\EditorJS\Config\EditorJSConfig;
use Symfony\UX\Editor\Bridge\EditorJS\Config\ToolDefinition;
use Symfony\UX\Editor\Bridge\Format\Block\BlockCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

final class EditorJSConfigTest extends TestCase
{
    public function testBridgeIdAndCapabilities(): void
    {
        $cfg = new EditorJSConfig();
        self::assertSame('editorjs', $cfg->getBridgeId());
        self::assertEquals(BlockCapabilities::default(), $cfg->getCapabilities());
    }

    public function testTranslateOwn(): void
    {
        $cfg = new EditorJSConfig(
            common: new CommonOptions(placeholder: 'Write…'),
            tools: ['header' => new ToolDefinition('Header', ['levels' => [2, 3, 4]])],
            defaultBlock: 'paragraph',
            minHeight: 200,
            logLevel: 'WARN',
        );
        $native = $cfg->toNative();
        self::assertSame('Write…', $native['placeholder']);
        self::assertSame('Header', $native['tools']['header']['class']);
        self::assertSame('paragraph', $native['defaultBlock']);
        self::assertSame(200, $native['minHeight']);
        self::assertSame('WARN', $native['logLevel']);
    }

    public function testNoToolsKeyWhenEmpty(): void
    {
        self::assertArrayNotHasKey('tools', (new EditorJSConfig())->toNative());
    }
}
