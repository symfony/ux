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
use Psr\Log\AbstractLogger;
use Symfony\UX\Editor\Config\AbstractEditorConfig;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Exception\IncompatibleConfigException;

final class CapabilityGuardTest extends TestCase
{
    public function testWarnsWhenIncompatibleAndNotStrict(): void
    {
        $logger = $this->arrayLogger();
        $c = $this->configWithToolbar(false);
        $c->setLogger($logger);
        $c->toNative();
        self::assertNotEmpty($logger->log);
        self::assertSame('warning', $logger->log[0][0]);
        self::assertStringContainsString('toolbar', $logger->log[0][1]);
    }

    public function testThrowsWhenIncompatibleAndStrict(): void
    {
        $c = $this->configWithToolbar(false);
        $c->setStrict(true);
        $this->expectException(IncompatibleConfigException::class);
        $c->toNative();
    }

    public function testSilentWhenCompatible(): void
    {
        $logger = $this->arrayLogger();
        $c = $this->configWithToolbar(true);
        $c->setLogger($logger);
        $c->toNative();
        self::assertSame([], $logger->log);
    }

    private function arrayLogger(): AbstractLogger
    {
        return new class extends AbstractLogger {
            public array $log = [];

            public function log($level, \Stringable|string $msg, array $ctx = []): void
            {
                $this->log[] = [$level, (string) $msg, $ctx];
            }
        };
    }

    private function configWithToolbar(bool $supportsToolbar): AbstractEditorConfig
    {
        return new class($supportsToolbar) extends AbstractEditorConfig {
            public function __construct(private bool $supportsToolbar)
            {
                parent::__construct(new CommonOptions(toolbar: ['bold']));
            }

            public function getBridgeId(): string
            {
                return 'fake';
            }

            public function getCapabilities(): BridgeCapabilities
            {
                return new BridgeCapabilities($this->supportsToolbar, true, true, true, ['html']);
            }

            protected function translateCommon(CommonOptions $c): array
            {
                return [];
            }
        };
    }
}
