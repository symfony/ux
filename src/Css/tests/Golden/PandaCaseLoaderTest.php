<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Golden;

use PHPUnit\Framework\TestCase;

final class PandaCaseLoaderTest extends TestCase
{
    public function testPerTestConfigsArePatchedOntoTheBaseConfig(): void
    {
        $id = 'fixture::packages/core/__tests__/rule-processor.test.ts::js to css > at-rules pseudo conditions sorting::1';
        $loader = new PandaCaseLoader(__DIR__.'/../Fixtures/Panda');
        $case = iterator_to_array($loader->cases())[$id];

        $config = $loader->configFor($case);
        $baseConfig = $loader->config('fixture');

        $this->assertSame('&[data-attr="custom"]', $config['conditions']['custom']);
        $this->assertSame($baseConfig['conditions']['hover'], $config['conditions']['hover']);
    }

    public function testConfigPatchRemovesNullKeysSetsValuesAndRecurses(): void
    {
        $config = ['a' => ['b' => 1, 'c' => 2], 'e' => [1, 2], 'f' => 6];
        $patch = [
            'a' => ['b' => null, 'c' => ['$value' => 3], 'd' => ['$value' => [0 => 'x']]],
            'e' => ['$value' => [5]],
            'f' => null,
        ];

        $patched = PandaCaseLoader::applyConfigPatch($config, $patch);

        $this->assertSame(['a' => ['c' => 3, 'd' => [0 => 'x']], 'e' => [5]], $patched);
    }

    public function testConfigPatchRestoresTheKeyOrder(): void
    {
        $config = ['tokens' => ['borders' => ['a' => 1], 'colors' => ['b' => 2]]];
        $patch = [
            'tokens' => [
                'shadows' => ['$value' => ['c' => 3]],
                '$order' => ['colors', 'shadows', 'borders'],
            ],
        ];

        $patched = PandaCaseLoader::applyConfigPatch($config, $patch);

        $this->assertSame(
            ['tokens' => ['colors' => ['b' => 2], 'shadows' => ['c' => 3], 'borders' => ['a' => 1]]],
            $patched,
        );
    }

    public function testTypeErrorsExpectedInsideTheCallAreRecorded(): void
    {
        $id = 'strict-tokens::sandbox/codegen/__tests__/scenarios/strict-tokens.test.ts::css > using inline token helper - in value::1';
        $loader = new PandaCaseLoader(__DIR__.'/../Fixtures/Panda');

        $case = iterator_to_array($loader->cases())[$id];

        $this->assertTrue($case->expectTypeError);
    }

    public function testTypeErrorsExpectedOnTheCallLineAreRecorded(): void
    {
        $id = 'strict-tokens::sandbox/codegen/__tests__/scenarios/strict-tokens.test.ts::css > native CSS prop and value::6';
        $loader = new PandaCaseLoader(__DIR__.'/../Fixtures/Panda');

        $case = iterator_to_array($loader->cases())[$id];

        $this->assertTrue($case->expectTypeError);
    }
}
