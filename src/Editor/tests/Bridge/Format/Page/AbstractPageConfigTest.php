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
use Symfony\UX\Editor\Bridge\Format\Page\AbstractPageConfig;
use Symfony\UX\Editor\Bridge\Format\Page\PageCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

final class AbstractPageConfigTest extends TestCase
{
    public function testCapabilitiesArePageFamily(): void
    {
        $cfg = new class extends AbstractPageConfig {
            public function getBridgeId(): string { return 'fp'; }
        };
        self::assertEquals(PageCapabilities::default(), $cfg->getCapabilities());
    }

    public function testTranslateCommonMinimal(): void
    {
        $cfg = new class(new CommonOptions(theme: 'dark', language: 'fr', placeholder: 'IGNORED')) extends AbstractPageConfig {
            public function getBridgeId(): string { return 'fp'; }
        };
        $n = $cfg->toNative();
        self::assertSame('dark', $n['theme']);
        self::assertSame('fr', $n['language']);
        self::assertArrayNotHasKey('placeholder', $n);
    }
}
