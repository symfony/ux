<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Block;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockBridge;
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockConfig;
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockTransformer;
use Symfony\UX\Editor\Bridge\Format\Block\BlockCapabilities;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class AbstractBlockBridgeTest extends TestCase
{
    public function testDefaults(): void
    {
        $b = new class extends AbstractBlockBridge {
            public function getId(): string { return 'fb'; }
            public function getDefaultConfig(): EditorConfigInterface
            {
                return new class extends AbstractBlockConfig {
                    public function getBridgeId(): string { return 'fb'; }
                };
            }
            public function createTransformer(): EditorContentTransformerInterface
            {
                return new class extends AbstractBlockTransformer {
                    public function getBridgeId(): string { return 'fb'; }
                };
            }
        };
        self::assertSame('symfony--ux-editor--fb', $b->getControllerName());
        self::assertEquals(BlockCapabilities::default(), $b->getCapabilities());
    }
}
