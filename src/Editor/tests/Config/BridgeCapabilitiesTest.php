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

final class BridgeCapabilitiesTest extends TestCase
{
    public function testFields(): void
    {
        $c = new BridgeCapabilities(true, true, false, true, ['html']);
        self::assertTrue($c->supportsToolbar);
        self::assertTrue($c->supportsPlugins);
        self::assertFalse($c->supportsTheme);
        self::assertTrue($c->supportsLanguage);
        self::assertSame(['html'], $c->supportedFormats);
    }

    public function testWithClonesAndOverrides(): void
    {
        $a = new BridgeCapabilities(true, true, true, true, ['html']);
        $b = $a->with(supportsTheme: false, supportedFormats: ['blocks']);
        self::assertTrue($a->supportsTheme);
        self::assertFalse($b->supportsTheme);
        self::assertSame(['blocks'], $b->supportedFormats);
        self::assertTrue($b->supportsToolbar);
    }
}
