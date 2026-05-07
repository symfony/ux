<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Command\DebugEditorCommand;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\Converter\ContentConverterRegistry;
use Symfony\UX\Editor\Upload\UploadHandlerRegistry;

final class DebugEditorCommandTest extends TestCase
{
    public function testListsSections(): void
    {
        $cmd = new DebugEditorCommand(
            new BridgeRegistry([]),
            new PresetRegistry([]),
            new ContentConverterRegistry([]),
            new UploadHandlerRegistry([]),
        );
        $tester = new CommandTester($cmd);
        $tester->execute([]);
        $out = $tester->getDisplay();
        self::assertStringContainsString('Bridges', $out);
        self::assertStringContainsString('Presets', $out);
        self::assertStringContainsString('Content converters', $out);
        self::assertStringContainsString('Upload handlers', $out);
        self::assertSame(0, $tester->getStatusCode());
    }
}
