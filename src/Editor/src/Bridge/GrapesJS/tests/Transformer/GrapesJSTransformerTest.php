<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\GrapesJS\Transformer\GrapesJSTransformer;
use Symfony\UX\Editor\Content\PageContent;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;

final class GrapesJSTransformerTest extends TestCase
{
    public function testMetadata(): void
    {
        $t = new GrapesJSTransformer();
        self::assertSame('grapesjs', $t->getBridgeId());
        self::assertSame(PageContent::class, $t->getContentClass());
        self::assertSame(StorageShape::Json, $t->getStorageShape());
    }

    public function testReverseStampsBridgeId(): void
    {
        $pc = new GrapesJSTransformer()->reverseTransform([
            'html' => '<h1>x</h1>',
            'css' => 'h1{}',
            'assets' => [],
            'components' => [['type' => 'h1']],
        ]);
        self::assertInstanceOf(PageContent::class, $pc);
        self::assertSame('<h1>x</h1>', $pc->html);
        self::assertSame('grapesjs', $pc->getMetadata()['bridgeId']);
    }
}
