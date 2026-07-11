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
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\QuoteRenderer;

final class QuoteRendererTest extends TestCase
{
    public function testTypeAndBasic()
    {
        $r = new QuoteRenderer();
        self::assertSame('quote', $r->getBlockType());
        self::assertSame('<blockquote><p>Be water</p></blockquote>', $r->render(['text' => 'Be water']));
    }

    public function testWithCaption()
    {
        self::assertSame('<blockquote><p>Be water</p><cite>Bruce Lee</cite></blockquote>', new QuoteRenderer()->render(['text' => 'Be water', 'caption' => 'Bruce Lee']));
    }
}
