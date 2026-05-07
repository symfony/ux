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
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygBridge;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygConfig;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygTransformer;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\WysiwygCapabilities;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class AbstractWysiwygBridgeTest extends TestCase
{
    public function testDefaults(): void
    {
        $b = new class extends AbstractWysiwygBridge {
            public function getId(): string { return 'fakewy'; }
            public function getDefaultConfig(): EditorConfigInterface
            {
                return new class extends AbstractWysiwygConfig {
                    public function getBridgeId(): string { return 'fakewy'; }
                };
            }
            public function createTransformer(): EditorContentTransformerInterface
            {
                return new class extends AbstractWysiwygTransformer {
                    public function getBridgeId(): string { return 'fakewy'; }
                };
            }
        };
        self::assertSame('symfony--ux-editor--fakewy', $b->getControllerName());
        self::assertEquals(WysiwygCapabilities::default(), $b->getCapabilities());
        self::assertInstanceOf(AbstractWysiwygConfig::class, $b->getDefaultConfig());
        self::assertInstanceOf(EditorContentTransformerInterface::class, $b->createTransformer());
    }
}
