<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Page;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Page\PageCapabilities;

final class PageCapabilitiesTest extends TestCase
{
    public function testDefaults()
    {
        $c = PageCapabilities::default();
        self::assertFalse($c->supportsToolbar);
        self::assertTrue($c->supportsPlugins);
        self::assertTrue($c->supportsTheme);
        self::assertTrue($c->supportsLanguage);
        self::assertSame(['page'], $c->supportedFormats);
    }
}
