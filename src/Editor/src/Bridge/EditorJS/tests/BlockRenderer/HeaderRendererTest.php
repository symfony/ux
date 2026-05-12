<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Tests\BlockRenderer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\HeaderRenderer;

final class HeaderRendererTest extends TestCase
{
    public function testType(): void
    {
        self::assertSame('header', (new HeaderRenderer())->getBlockType());
    }

    public function testRendersLevels(): void
    {
        $r = new HeaderRenderer();
        self::assertSame('<h2>Title</h2>', $r->render(['text' => 'Title', 'level' => 2]));
        self::assertSame('<h4>Sub</h4>', $r->render(['text' => 'Sub', 'level' => 4]));
    }

    public function testClampsLevel(): void
    {
        $r = new HeaderRenderer();
        self::assertSame('<h2>X</h2>', $r->render(['text' => 'X', 'level' => 1]));
        self::assertSame('<h6>X</h6>', $r->render(['text' => 'X', 'level' => 9]));
    }
}
