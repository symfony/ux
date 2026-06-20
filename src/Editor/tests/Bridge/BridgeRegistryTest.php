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
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Exception\UnknownBridgeException;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;

final class BridgeRegistryTest extends TestCase
{
    public function testGetAndAll(): void
    {
        $a = $this->fakeBridge('a');
        $b = $this->fakeBridge('b');
        $reg = new BridgeRegistry([$a, $b]);
        self::assertSame($a, $reg->get('a'));
        self::assertSame($b, $reg->get('b'));
        self::assertSame(['a', 'b'], array_keys($reg->all()));
    }

    public function testUnknownThrows(): void
    {
        $this->expectException(UnknownBridgeException::class);
        new BridgeRegistry([])->get('missing');
    }

    public function testDuplicateIdThrows(): void
    {
        $this->expectException(\LogicException::class);
        new BridgeRegistry([$this->fakeBridge('a'), $this->fakeBridge('a')]);
    }

    private function fakeBridge(string $id): BridgeInterface
    {
        return new class($id) implements BridgeInterface {
            public function __construct(private string $id)
            {
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getControllerName(): string
            {
                return 'symfony--ux-editor--'.$this->id;
            }

            public function getDefaultConfig(): EditorConfigInterface
            {
                throw new \LogicException('not used');
            }

            public function getCapabilities(): BridgeCapabilities
            {
                return new BridgeCapabilities(true, true, true, true, ['html']);
            }

            public function createTransformer(): EditorContentTransformerInterface
            {
                throw new \LogicException('not used');
            }
        };
    }
}
