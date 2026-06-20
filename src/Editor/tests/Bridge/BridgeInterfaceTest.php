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
use Symfony\UX\Editor\Bridge\BridgeInterface;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class BridgeInterfaceTest extends TestCase
{
    public function testContract(): void
    {
        $b = new class implements BridgeInterface {
            public function getId(): string
            {
                return 'fake';
            }

            public function getControllerName(): string
            {
                return 'symfony--ux-editor--fake';
            }

            public function getDefaultConfig(): EditorConfigInterface
            {
                return new class implements EditorConfigInterface {
                    public function getBridgeId(): string
                    {
                        return 'fake';
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
        self::assertSame('fake', $b->getId());
        self::assertSame('symfony--ux-editor--fake', $b->getControllerName());
        self::assertContains('html', $b->getCapabilities()->supportedFormats);
    }
}
