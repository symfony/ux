<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Config\Preset;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Config\Preset\EditorPresetInterface;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Exception\UnknownBridgeException;

final class PresetRegistryTest extends TestCase
{
    public function testGetByName(): void
    {
        $reg = new PresetRegistry(['blog.standard' => $this->fakePreset('eid')]);
        self::assertSame('eid', $reg->get('blog.standard')->build()->getBridgeId());
    }

    public function testUnknownPresetThrows(): void
    {
        $this->expectException(UnknownBridgeException::class);
        new PresetRegistry([])->get('does.not.exist');
    }

    public function testAll(): void
    {
        $reg = new PresetRegistry(['a' => $this->fakePreset('a'), 'b' => $this->fakePreset('b')]);
        self::assertSame(['a', 'b'], array_keys($reg->all()));
    }

    private function fakePreset(string $bridgeId): EditorPresetInterface
    {
        return new class($bridgeId) implements EditorPresetInterface {
            public function __construct(private string $bridgeId)
            {
            }

            public function build(): EditorConfigInterface
            {
                $bid = $this->bridgeId;

                return new class($bid) implements EditorConfigInterface {
                    public function __construct(private string $bid)
                    {
                    }

                    public function getBridgeId(): string
                    {
                        return $this->bid;
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
        };
    }
}
