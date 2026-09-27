<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\PandaConfig;

final class PandaConfigTest extends TestCase
{
    public function testThePresetWithTheFixtureTokensAndConditionsIsTheFixtureConfig(): void
    {
        $json = file_get_contents(__DIR__.'/../Fixtures/Panda/configs/fixture.json');
        $fixture = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
        $conditionNames = ['materialTheme', 'pastelTheme', 'dark', 'light'];

        $config = PandaConfig::create([
            'conditions' => array_intersect_key($fixture['conditions'], array_flip($conditionNames)),
            'theme' => [
                'tokens' => $fixture['theme']['tokens'],
                'semanticTokens' => $fixture['theme']['semanticTokens'],
            ],
        ]);

        $this->assertSame($fixture['utilities'], $config['utilities']);
        $this->assertEqualsCanonicalizing($fixture['conditions'], $config['conditions']);
        $this->assertSame($fixture['theme']['breakpoints'], $config['theme']['breakpoints']);
        $this->assertSame($fixture['theme']['containerSizes'], $config['theme']['containerSizes']);
        $this->assertSame($fixture['theme']['tokens'], $config['theme']['tokens']);
    }

    public function testProjectConditionsAreAddedOrReplaceTheDefaultOnes(): void
    {
        $config = PandaConfig::create(['conditions' => ['dark' => '[data-theme=dark] &', 'print' => '@media print']]);

        $this->assertSame('[data-theme=dark] &', $config['conditions']['dark']);
        $this->assertSame('@media print', $config['conditions']['print']);
        $this->assertSame('&:is(:hover, [data-hover])', $config['conditions']['hover']);
    }

    public function testProjectBreakpointsReplaceTheDefaultOnes(): void
    {
        $config = PandaConfig::create(['theme' => ['breakpoints' => ['tablet' => '48rem']]]);

        $this->assertSame(['tablet' => '48rem'], $config['theme']['breakpoints']);
        $this->assertArrayHasKey('containerSizes', $config['theme']);
    }

    public function testThereAreNoDefaultTokens(): void
    {
        $config = PandaConfig::create([]);

        $this->assertArrayNotHasKey('tokens', $config['theme']);
    }
}
