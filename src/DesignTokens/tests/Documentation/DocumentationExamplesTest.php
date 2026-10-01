<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Documentation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\TokenRegistry;
use Symfony\UX\DesignTokens\Twig\DesignTokenExtension;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Source;

final class DocumentationExamplesTest extends TestCase
{
    /** @return iterable<string, array{string, string, string}> */
    public static function examples(): iterable
    {
        $root = \dirname(__DIR__, 2);
        $files = [$root.'/README.md'];

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);
            preg_match_all('/^```(json|yaml|php|twig|css)\h*\R(.*?)^```\h*$/ms', $contents, $matches, \PREG_OFFSET_CAPTURE);

            foreach ($matches[1] as $index => [$language]) {
                [$example, $offset] = $matches[2][$index];
                $line = 1 + substr_count(substr($contents, 0, $offset), "\n");
                $name = \sprintf('%s:%d (%s)', basename($file), $line, $language);

                yield $name => [$language, $example, $name];
            }
        }

        foreach (glob($root.'/doc/*.rst') ?: [] as $file) {
            $contents = (string) file_get_contents($file);

            foreach (self::rstExamples($contents) as [$language, $example, $line]) {
                $name = \sprintf('%s:%d (%s)', basename($file), $line, $language);

                yield $name => [$language, $example, $name];
            }
        }
    }

    #[DataProvider('examples')]
    public function testDisplayedExampleIsSyntacticallyExecutable(string $language, string $example, string $name): void
    {
        try {
            match ($language) {
                'json' => self::validateJson($example),
                'yaml' => Yaml::parse($example),
                'php' => \PhpToken::tokenize(str_starts_with(ltrim($example), '<?php') ? $example : "<?php\n".$example, \TOKEN_PARSE),
                'twig', 'html+twig' => self::parseTwig($example, $name),
                'css' => self::assertBalancedCss($example),
            };
        } catch (\JsonException|\ParseError|SyntaxError $error) {
            self::fail($name.': '.$error->getMessage());
        }

        self::addToAssertionCount(1);
    }

    /** @return iterable<array{string, string, int}> */
    private static function rstExamples(string $contents): iterable
    {
        $lines = preg_split('/\R/', $contents);
        \assert(\is_array($lines));

        for ($index = 0; $index < \count($lines); ++$index) {
            $literalPhp = str_ends_with($lines[$index], '::');
            if (!$literalPhp && 1 !== preg_match('/^\.\. code-block:: (json|yaml|php|twig|html\+twig|css)$/', $lines[$index], $matches)) {
                continue;
            }

            $line = $index + 1;
            while (isset($lines[$index + 1]) && ('' === $lines[$index + 1] || 1 === preg_match('/^    :[\w.-]+:(\s|$)/', $lines[$index + 1]))) {
                ++$index;
            }

            $example = [];
            while (isset($lines[$index + 1]) && ('' === $lines[$index + 1] || str_starts_with($lines[$index + 1], '    '))) {
                ++$index;
                $example[] = '' === $lines[$index] ? '' : substr($lines[$index], 4);
            }

            while ([] !== $example && '' === $example[array_key_last($example)]) {
                array_pop($example);
            }

            if ($literalPhp && !str_ends_with($example[0] ?? '', '.php')) {
                continue;
            }

            yield [$literalPhp ? 'php' : $matches[1], implode("\n", $example)."\n", $line];
        }
    }

    private static function assertBalancedCss(string $css): null
    {
        self::assertSame(substr_count($css, '{'), substr_count($css, '}'), 'CSS braces are not balanced.');

        return null;
    }

    private static function parseTwig(string $twig, string $name): null
    {
        $environment = new Environment(new ArrayLoader());
        $environment->addExtension(new DesignTokenExtension(new TokenRegistry()));
        $environment->parse($environment->tokenize(new Source($twig, $name)));

        return null;
    }

    private static function validateJson(string $json): null
    {
        $document = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        if (\is_array($document) && !isset($document['$schema']) && self::containsToken($document)) {
            new TokenTreeBuilder()->resolve($document);
        }

        return null;
    }

    /** @param array<array-key, mixed> $node */
    private static function containsToken(array $node): bool
    {
        if (\array_key_exists('$value', $node)) {
            return true;
        }

        foreach ($node as $value) {
            if (\is_array($value) && self::containsToken($value)) {
                return true;
            }
        }

        return false;
    }
}
