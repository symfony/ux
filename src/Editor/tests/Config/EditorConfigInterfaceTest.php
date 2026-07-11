<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Config;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;

final class EditorConfigInterfaceTest extends TestCase
{
    public function testContractMethodsExist()
    {
        $stub = new class implements EditorConfigInterface {
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
        self::assertSame('fake', $stub->getBridgeId());
        self::assertInstanceOf(CommonOptions::class, $stub->getCommon());
        self::assertInstanceOf(BridgeCapabilities::class, $stub->getCapabilities());
        self::assertSame([], $stub->getNativeOverrides());
        self::assertSame([], $stub->toNative());
    }
}
