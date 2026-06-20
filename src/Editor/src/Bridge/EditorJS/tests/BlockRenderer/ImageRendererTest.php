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
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ImageRenderer;

final class ImageRendererTest extends TestCase
{
    public function testTypeAndUrl(): void
    {
        $r = new ImageRenderer();
        self::assertSame('image', $r->getBlockType());
        self::assertSame('<figure><img src="/x.png" alt=""></figure>', $r->render(['file' => ['url' => '/x.png']]));
    }

    public function testWithCaption(): void
    {
        self::assertSame(
            '<figure><img src="/x.png" alt="alt"><figcaption>cap</figcaption></figure>',
            new ImageRenderer()->render(['file' => ['url' => '/x.png'], 'caption' => 'cap', 'alt' => 'alt'])
        );
    }

    public function testMissingUrlEmpty(): void
    {
        self::assertSame('', new ImageRenderer()->render([]));
    }
}
