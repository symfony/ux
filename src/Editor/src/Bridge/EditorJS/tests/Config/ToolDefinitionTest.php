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
use Symfony\UX\Editor\Bridge\EditorJS\Config\ToolDefinition;

final class ToolDefinitionTest extends TestCase
{
    public function testDefaults(): void
    {
        $t = new ToolDefinition('Header');
        self::assertSame('Header', $t->class);
        self::assertSame([], $t->config);
        self::assertTrue($t->inlineToolbar);
        self::assertNull($t->shortcut);
    }

    public function testToArray(): void
    {
        $t = new ToolDefinition('Image', ['endpoints' => ['byFile' => '/u']], inlineToolbar: false, shortcut: 'CMD+I');
        self::assertSame([
            'class' => 'Image',
            'inlineToolbar' => false,
            'config' => ['endpoints' => ['byFile' => '/u']],
            'shortcut' => 'CMD+I',
        ], $t->toArray());
    }

    public function testToArrayOmitsEmptyConfig(): void
    {
        $arr = new ToolDefinition('Header')->toArray();
        self::assertSame('Header', $arr['class']);
        self::assertArrayNotHasKey('config', $arr);
        self::assertArrayNotHasKey('shortcut', $arr);
    }
}
