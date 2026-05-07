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
use Symfony\UX\Editor\Config\AbstractEditorConfig;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;

final class AbstractEditorConfigTest extends TestCase
{
    public function testMergeOrderCommonOwnOverrides(): void
    {
        $c = new class(
            common: new CommonOptions(placeholder: 'common-ph'),
            nativeOverrides: ['placeholder' => 'override-ph', 'extra' => true],
        ) extends AbstractEditorConfig {
            public function getBridgeId(): string { return 'fake'; }
            public function getCapabilities(): BridgeCapabilities { return new BridgeCapabilities(true, true, true, true, ['html']); }
            protected function translateCommon(CommonOptions $c): array {
                return ['placeholder' => $c->placeholder, 'origin' => 'common'];
            }
            protected function translateOwn(): array { return ['origin' => 'own']; }
        };
        $native = $c->toNative();
        self::assertSame('override-ph', $native['placeholder']);
        self::assertSame('own', $native['origin']);
        self::assertTrue($native['extra']);
    }

    public function testCommonReturnedAsGiven(): void
    {
        $c = new class(common: new CommonOptions(language: 'fr')) extends AbstractEditorConfig {
            public function getBridgeId(): string { return 'fake'; }
            public function getCapabilities(): BridgeCapabilities { return new BridgeCapabilities(true, true, true, true, ['html']); }
            protected function translateCommon(CommonOptions $c): array { return []; }
        };
        self::assertSame('fr', $c->getCommon()->language);
        self::assertSame([], $c->getNativeOverrides());
    }
}
