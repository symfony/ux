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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\PandaConfig;
use Symfony\UX\Css\Engine\Preset\BaseTransforms;
use Symfony\UX\Css\Engine\Tokens;
use Symfony\UX\Css\Engine\Utilities;
use Symfony\UX\Css\Exception\ExceptionInterface;

final class PandaUtilitiesTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../Fixtures/Panda';

    private static ?Utilities $utilities = null;

    public static function provideTransforms(): iterable
    {
        $cases = json_decode(file_get_contents(self::FIXTURES.'/utilities.json'), true, flags: \JSON_THROW_ON_ERROR);
        foreach ($cases as $property => $entries) {
            foreach ($entries as $entry) {
                yield $property.' '.json_encode($entry[0], \JSON_UNESCAPED_SLASHES) => [$property, ...$entry];
            }
        }
    }

    #[DataProvider('provideTransforms')]
    public function testTransformMatchesPanda(
        string $property,
        string|int|float|bool $value,
        string|array $className,
        array $styles = [],
        ?string $layer = null,
    ): void {
        if ('compositions' === $layer) {
            $this->markTestSkipped('Text styles and layer styles are out of v1.');
        }
        if (\is_array($className)) {
            $this->expectException(ExceptionInterface::class);
        }

        $transformed = self::utilities()->transform($property, $value);

        $expected = ['className' => $className, 'styles' => $styles];
        if (null !== $layer) {
            $expected['layer'] = $layer;
        }
        $this->assertSame($expected, $transformed);
    }

    private static function utilities(): Utilities
    {
        if (null !== self::$utilities) {
            return self::$utilities;
        }

        $json = file_get_contents(self::FIXTURES.'/configs/fixture.json');
        $fixture = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
        $config = PandaConfig::create([
            'theme' => [
                'tokens' => $fixture['theme']['tokens'],
                'semanticTokens' => $fixture['theme']['semanticTokens'],
            ],
        ]);
        $tokens = new Tokens(
            $config['theme']['tokens'],
            $config['theme']['semanticTokens'],
            $config['theme']['breakpoints'],
        );

        return self::$utilities = new Utilities($config['utilities'], '_', $tokens, BaseTransforms::all());
    }
}
