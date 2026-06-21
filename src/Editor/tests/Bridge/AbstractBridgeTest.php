<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\AbstractBridge;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class AbstractBridgeTest extends TestCase
{
    public function testDefaultControllerNameDerivedFromId()
    {
        $b = new class extends AbstractBridge {
            public function getId(): string
            {
                return 'fakebridge';
            }

            public function getDefaultConfig(): EditorConfigInterface
            {
                return new class implements EditorConfigInterface {
                    public function getBridgeId(): string
                    {
                        return 'fakebridge';
                    }

                    public function getCommon(): CommonOptions
                    {
                        return new CommonOptions();
                    }

                    public function getNativeOverrides(): array
                    {
                        return [];
                    }

                    public function getCapabilities(): BridgeCapabilities
                    {
                        return new BridgeCapabilities(true, true, true, true, ['html']);
                    }

                    public function toNative(): array
                    {
                        return [];
                    }
                };
            }

            public function getCapabilities(): BridgeCapabilities
            {
                return $this->getDefaultConfig()->getCapabilities();
            }

            public function createTransformer(): EditorContentTransformerInterface
            {
                throw new \LogicException('not used');
            }
        };
        self::assertSame('symfony--ux-editor--fakebridge', $b->getControllerName());
    }
}
