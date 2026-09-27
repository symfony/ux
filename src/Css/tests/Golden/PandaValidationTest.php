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
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Exception\InvalidStyleException;
use Symfony\UX\Css\Validation\StyleValidator;

final class PandaValidationTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../Fixtures/Panda';

    /**
     * @var array<string, StyleValidator>
     */
    private static array $validators = [];

    public static function provideCases(): iterable
    {
        foreach (new PandaCaseLoader(self::FIXTURES)->cases() as $id => $case) {
            if ('codegen-css' === $case->kind && null === $case->unsupported) {
                yield $id => [$case];
            }
        }
    }

    #[DataProvider('provideCases')]
    public function testTheValidationRejectsWhatPandaTypesReject(PandaCase $case): void
    {
        if ($case->expectTypeError && $case->undefinedValues) {
            $this->markTestSkipped('Panda\'s types reject an undefined item, recorded as null: Twig has no undefined.');
        }

        $errors = [];
        foreach (self::styles($case->inputs) as $styles) {
            try {
                self::validator($case->config)->validate($styles);
            } catch (InvalidStyleException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($case->expectTypeError) {
            $this->assertNotSame([], $errors, 'Panda\'s types reject this input, see '.$case->sourceLocation());
        } else {
            $this->assertSame([], $errors, 'Panda\'s types accept this input, see '.$case->sourceLocation());
        }
    }

    /**
     * @param list<mixed> $inputs
     *
     * @return iterable<array<array-key, mixed>>
     */
    private static function styles(array $inputs): iterable
    {
        foreach ($inputs as $input) {
            foreach (\is_array($input) && array_is_list($input) ? $input : [$input] as $styles) {
                if (\is_array($styles) && [] !== $styles && !array_is_list($styles)) {
                    yield $styles;
                }
            }
        }
    }

    private static function validator(string $name): StyleValidator
    {
        if (isset(self::$validators[$name])) {
            return self::$validators[$name];
        }

        $config = new PandaCaseLoader(self::FIXTURES)->config($name);

        return self::$validators[$name] = new StyleValidator(
            new Engine($config),
            (bool) ($config['strictTokens'] ?? false),
            (bool) ($config['strictPropertyValues'] ?? false),
        );
    }
}
