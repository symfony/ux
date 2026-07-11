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
use Psr\Log\AbstractLogger;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygConfig;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\WysiwygCapabilities;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

final class WysiwygCapabilityWarningTest extends TestCase
{
    public function testWarnsWhenToolbarUnsupported()
    {
        $logger = new class extends AbstractLogger {
            public array $log = [];

            public function log($level, \Stringable|string $msg, array $ctx = []): void
            {
                $this->log[] = [$level, (string) $msg];
            }
        };
        $cfg = new class(new CommonOptions(toolbar: ['bold'])) extends AbstractWysiwygConfig {
            public function getBridgeId(): string
            {
                return 'notoolbar';
            }

            public function getCapabilities(): BridgeCapabilities
            {
                return WysiwygCapabilities::default()->with(supportsToolbar: false);
            }
        };
        $cfg->setLogger($logger);
        $cfg->toNative();

        self::assertNotEmpty($logger->log);
        self::assertSame('warning', $logger->log[0][0]);
        self::assertStringContainsString('toolbar', $logger->log[0][1]);
    }
}
