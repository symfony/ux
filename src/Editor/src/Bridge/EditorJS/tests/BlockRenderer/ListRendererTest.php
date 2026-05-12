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
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ListRenderer;

final class ListRendererTest extends TestCase
{
    public function testTypeAndUnorderedDefault(): void
    {
        $r = new ListRenderer();
        self::assertSame('list', $r->getBlockType());
        self::assertSame('<ul><li>a</li><li>b</li></ul>', $r->render(['items' => ['a', 'b']]));
    }

    public function testOrdered(): void
    {
        self::assertSame('<ol><li>a</li></ol>', (new ListRenderer())->render(['style' => 'ordered', 'items' => ['a']]));
    }

    public function testEscapesItems(): void
    {
        self::assertSame('<ul><li>&lt;b&gt;x&lt;/b&gt;</li></ul>', (new ListRenderer())->render(['items' => ['<b>x</b>']]));
    }
}
