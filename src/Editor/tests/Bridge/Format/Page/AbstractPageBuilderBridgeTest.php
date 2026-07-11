<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Page;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Page\AbstractPageBuilderBridge;
use Symfony\UX\Editor\Bridge\Format\Page\AbstractPageConfig;
use Symfony\UX\Editor\Bridge\Format\Page\AbstractPageTransformer;
use Symfony\UX\Editor\Bridge\Format\Page\PageCapabilities;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class AbstractPageBuilderBridgeTest extends TestCase
{
    public function testDefaults()
    {
        $b = new class extends AbstractPageBuilderBridge {
            public function getId(): string
            {
                return 'fp';
            }

            public function getDefaultConfig(): EditorConfigInterface
            {
                return new class extends AbstractPageConfig {
                    public function getBridgeId(): string
                    {
                        return 'fp';
                    }
                };
            }

            public function createTransformer(): EditorContentTransformerInterface
            {
                return new class extends AbstractPageTransformer {
                    public function getBridgeId(): string
                    {
                        return 'fp';
                    }
                };
            }
        };
        self::assertSame('symfony--ux-editor--fp', $b->getControllerName());
        self::assertEquals(PageCapabilities::default(), $b->getCapabilities());
    }
}
