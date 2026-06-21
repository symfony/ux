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
use Symfony\UX\Editor\Bridge\Format\Block\BlockCapabilities;

final class BlockCapabilitiesTest extends TestCase
{
    public function testDefaults()
    {
        $c = BlockCapabilities::default();
        self::assertFalse($c->supportsToolbar);
        self::assertTrue($c->supportsPlugins);
        self::assertFalse($c->supportsTheme);
        self::assertTrue($c->supportsLanguage);
        self::assertSame(['blocks'], $c->supportedFormats);
    }
}
