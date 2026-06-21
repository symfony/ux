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
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockConfig;
use Symfony\UX\Editor\Bridge\Format\Block\BlockCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

final class AbstractBlockConfigTest extends TestCase
{
    public function testDefaultTranslateCommon()
    {
        $cfg = new class(new CommonOptions(placeholder: 'P', autofocus: true, readOnly: true)) extends AbstractBlockConfig {
            public function getBridgeId(): string
            {
                return 'fb';
            }
        };
        $n = $cfg->toNative();
        self::assertSame('P', $n['placeholder']);
        self::assertTrue($n['autofocus']);
        self::assertTrue($n['readOnly']);
    }

    public function testCapabilitiesAreBlockFamily()
    {
        $cfg = new class extends AbstractBlockConfig {
            public function getBridgeId(): string
            {
                return 'fb';
            }
        };
        self::assertEquals(BlockCapabilities::default(), $cfg->getCapabilities());
    }
}
