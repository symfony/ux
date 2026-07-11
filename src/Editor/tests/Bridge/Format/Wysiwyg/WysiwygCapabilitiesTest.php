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
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\WysiwygCapabilities;

final class WysiwygCapabilitiesTest extends TestCase
{
    public function testDefaults()
    {
        $c = WysiwygCapabilities::default();
        self::assertTrue($c->supportsToolbar);
        self::assertTrue($c->supportsPlugins);
        self::assertTrue($c->supportsTheme);
        self::assertTrue($c->supportsLanguage);
        self::assertSame(['html'], $c->supportedFormats);
    }

    public function testWithOverrides()
    {
        $c = WysiwygCapabilities::default()->with(supportsTheme: false);
        self::assertFalse($c->supportsTheme);
        self::assertTrue($c->supportsToolbar);
        self::assertSame(['html'], $c->supportedFormats);
    }
}
