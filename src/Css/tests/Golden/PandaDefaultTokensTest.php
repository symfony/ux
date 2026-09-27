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
use Symfony\UX\Css\DependencyInjection\PandaConfigConverter;
use Symfony\UX\Css\Engine\Engine;

final class PandaDefaultTokensTest extends TestCase
{
    private const DEFAULT_TOKENS = __DIR__.'/../../resources/panda-tokens.json';
    private const PANDA_TOKENS = __DIR__.'/../Fixtures/Panda/tokens.json';

    public function testTheDefaultTokensHaveTheValuesPandaGivesThem(): void
    {
        $defaults = json_decode(file_get_contents(self::DEFAULT_TOKENS), true, flags: \JSON_THROW_ON_ERROR);
        $config = ['tokens' => $defaults['tokens'], 'semantic_tokens' => [], 'conditions' => [], 'breakpoints' => []];
        $tokens = Engine::fromProjectConfig(PandaConfigConverter::convert($config))->tokens();
        $expected = json_decode(file_get_contents(self::PANDA_TOKENS), true, flags: \JSON_THROW_ON_ERROR)['values'];

        $compared = 0;
        foreach ($expected as $name => $value) {
            $category = explode('.', (string) $name)[0];
            $ours = $tokens->getValue((string) $name);
            if (!\in_array($category, PandaConfigConverter::CATEGORIES, true) || null === $ours) {
                continue;
            }

            $this->assertSame($value, $ours, $name);
            ++$compared;
        }

        $this->assertGreaterThan(400, $compared);
    }
}
