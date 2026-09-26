<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\UX\DesignTokens\Command\ExportCommand;
use Symfony\UX\DesignTokens\Generator\ColorScheme;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Generator\DtcgGenerator;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Generator\JavaScriptGenerator;
use Symfony\UX\DesignTokens\Tests\Fixtures\Registries;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;
use Symfony\UX\DesignTokens\Tests\Fixtures\TokenValues;
use Symfony\UX\DesignTokens\TokenRegistry;

#[CoversClass(ExportCommand::class)]
final class ExportCommandTest extends TestCase
{
    private TokenRegistry $tokenRegistry;
    private CommandTester $tester;
    private TemporaryDirectory $directory;

    protected function setUp(): void
    {
        $this->tokenRegistry = Registries::fromJson(json_encode([
            'color' => [
                'brand' => [
                    '$type' => 'color',
                    '$value' => TokenValues::color(),
                ],
            ],
            'spacing' => [
                '$type' => 'dimension',
                'md' => ['$value' => TokenValues::dimension(16)],
            ],
        ], \JSON_THROW_ON_ERROR));

        $this->tester = new CommandTester(self::command($this->tokenRegistry));
        $this->directory = new TemporaryDirectory();
    }

    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    /**
     * @param array<string, mixed> $input
     * @param list<string>         $expected
     */
    #[DataProvider('stdoutExports')]
    public function testExportsToStdout(array $input, array $expected): void
    {
        $this->tester->execute($input);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        foreach ($expected as $fragment) {
            self::assertStringContainsString($fragment, $this->tester->getDisplay());
        }
    }

    /** @return iterable<string, array{array<string, mixed>, list<string>}> */
    public static function stdoutExports(): iterable
    {
        yield 'css' => [['format' => 'css'], [':root {', '--dt-color-brand']];
        yield 'css with an explicit application prefix' => [['format' => 'css', '--css-prefix' => 'my'], ['--my-color-brand']];
        yield 'format spelled in another case' => [['format' => 'CSS'], [':root {']];
        yield 'javascript' => [['format' => 'javascript'], [
            'export const tokens = Object.freeze(JSON.parse(',
            '\\"color.brand\\":\\"color(srgb 0.2 0.4 0.8)\\"',
            'export function token(path)',
        ]];
    }

    /** @param array<string, mixed> $input */
    #[DataProvider('rejectedExports')]
    public function testRejectsAnExportItCannotRun(array $input, int $status, string $message): void
    {
        $this->tester->execute($input);

        self::assertSame($status, $this->tester->getStatusCode());
        self::assertStringContainsString($message, $this->tester->getDisplay());
    }

    /** @return iterable<string, array{array<string, mixed>, int, string}> */
    public static function rejectedExports(): iterable
    {
        yield 'invalid css prefix' => [['format' => 'css', '--css-prefix' => '--my'], Command::FAILURE, 'CSS prefix'];
        yield 'unknown format' => [['format' => 'xml'], Command::INVALID, 'Unknown format'];
        yield 'malformed input' => [['format' => 'dtcg', '--input' => ['scheme']], Command::FAILURE, 'name=value'];
        yield 'permutations without a resolver document' => [['format' => 'dtcg', 'output' => '/tmp/unused.json', '--all-permutations' => true], Command::FAILURE, 'Resolver document'];
        yield 'permutations without an output path' => [['format' => 'dtcg', '--all-permutations' => true], Command::FAILURE, 'output path'];
    }

    public function testExportCssWritesTheDarkColorScheme(): void
    {
        $tester = new CommandTester(self::command(Registries::colorScheme()));
        $tester->execute(['format' => 'css', '--css-prefix' => 'my']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        [$root, $dark] = explode('@media (prefers-color-scheme: dark)', $tester->getDisplay(), 2);
        self::assertStringContainsString('--my-color-action-primary: color(srgb 0.2 0.4 0.8);', $root);
        self::assertStringContainsString('--my-color-action-primary: color(srgb 0.5 0.6 0.9);', $dark);
        self::assertStringContainsString(':root[data-theme="dark"]', $dark);
        self::assertStringNotContainsString('--my-dimension-spacing-md', $dark);
    }

    public function testExportCssOfASelectedSchemeHasNoDarkBlock(): void
    {
        $tester = new CommandTester(self::command(Registries::colorScheme()));
        $tester->execute(['format' => 'css', '--input' => ['scheme=dark']]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.5 0.6 0.9);', $tester->getDisplay());
        self::assertStringNotContainsString('prefers-color-scheme', $tester->getDisplay());
    }

    public function testExportCssUsesTheConfiguredColorScheme(): void
    {
        $generators = new ServiceLocator(['css' => static fn (): GeneratorInterface => new CssGenerator()]);
        $configured = new CommandTester(new ExportCommand(Registries::colorScheme('named-theme.resolver.json'), $generators, colorScheme: new ColorScheme('mode', 'day', 'night')));
        $default = new CommandTester(new ExportCommand(Registries::colorScheme('named-theme.resolver.json'), $generators));

        $configured->execute(['format' => 'css']);
        $default->execute(['format' => 'css']);

        self::assertStringContainsString('prefers-color-scheme', $configured->getDisplay());
        self::assertStringNotContainsString('prefers-color-scheme', $default->getDisplay());
    }

    public function testExportDtcgToStdout(): void
    {
        $this->tester->execute(['format' => 'dtcg']);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());

        $data = json_decode($this->tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('color', $data['color']['brand']['$type']);
        self::assertSame(TokenValues::color(), $data['color']['brand']['$value']);
        self::assertSame('dimension', $data['spacing']['md']['$type']);
    }

    public function testExportToFile(): void
    {
        $outputPath = $this->directory->path('tokens.css');

        $this->tester->execute(['format' => 'css', 'output' => $outputPath]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertFileExists($outputPath);
        self::assertStringContainsString(':root {', file_get_contents($outputPath));
    }

    public function testExportCreatesTheOutputDirectory(): void
    {
        $outputPath = $this->directory->path('export/nested/tokens.css');

        $this->tester->execute(['format' => 'css', 'output' => $outputPath]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertFileExists($outputPath);
    }

    public function testExportReportsAnUnwritableOutputPath(): void
    {
        $blocker = $this->directory->write('blocker', '');

        $this->tester->execute(['format' => 'css', 'output' => $blocker.'/tokens.css']);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('Could not write export', $this->tester->getDisplay());
    }

    public function testExportSelectsAResolverContextWithInput(): void
    {
        $tester = $this->resolverTester();

        $tester->execute(['format' => 'dtcg', '--input' => ['scheme=dark']]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('"$value": 2', $tester->getDisplay());
    }

    public function testExportWritesOneFilePerPermutation(): void
    {
        $tester = $this->resolverTester();

        $tester->execute([
            'format' => 'dtcg',
            'output' => $this->directory->path('permutations/theme.tokens.json'),
            '--all-permutations' => true,
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileExists($light = $this->directory->path('permutations/theme.scheme-light.tokens.json'));
        self::assertFileExists($dark = $this->directory->path('permutations/theme.scheme-dark.tokens.json'));
        self::assertStringContainsString('"$value": 1', (string) file_get_contents($light));
        self::assertStringContainsString('"$value": 2', (string) file_get_contents($dark));
    }

    public function testExportRefusesTwoPermutationsSharingAFileName(): void
    {
        $path = $this->directory->write('export.resolver.json', [
            'version' => '2025.10',
            'modifiers' => ['size' => ['contexts' => [
                'a b' => [['n' => ['$type' => 'number', '$value' => 1]]],
                'a-b' => [['n' => ['$type' => 'number', '$value' => 2]]],
            ], 'default' => 'a b']],
            'resolutionOrder' => [['$ref' => '#/modifiers/size']],
        ]);
        $directory = $this->directory->path('permutations');

        $tester = new CommandTester(self::command(Registries::fromFiles(resolverPath: $path)));
        $tester->execute(['format' => 'dtcg', 'output' => $directory.'/theme.tokens.json', '--all-permutations' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('same file', $tester->getDisplay());
        self::assertDirectoryDoesNotExist($directory);
    }

    public function testAnyGeneratorReceivesTheExportContext(): void
    {
        $generator = new class implements GeneratorInterface {
            /** @var array<string, mixed> */
            public array $context = [];

            public function generate(array $resolvedTokens, array $context = []): string
            {
                $this->context = $context;

                return 'custom';
            }
        };

        $tester = new CommandTester(new ExportCommand(Registries::fromFiles(resolverPath: $this->writeSchemeResolver()), new ServiceLocator(['custom' => static fn (): GeneratorInterface => $generator])));
        $tester->execute(['format' => 'custom', '--title' => 'Acme', '--css-prefix' => 'app']);

        self::assertSame('Acme', $generator->context[GeneratorInterface::TITLE]);
        self::assertSame('app', $generator->context[GeneratorInterface::CSS_PREFIX]);
        self::assertSame(2, $generator->context[GeneratorInterface::DARK_TOKENS]['theme']->getValue());
    }

    public function testAFormatKeyedByAServiceIdIsFoundWhateverItsCase(): void
    {
        $generator = new class implements GeneratorInterface {
            public function generate(array $resolvedTokens, array $context = []): string
            {
                return 'custom';
            }
        };
        $tester = new CommandTester(new ExportCommand($this->tokenRegistry, new ServiceLocator(['App\\Export\\Scss' => static fn (): GeneratorInterface => $generator])));

        self::assertSame(Command::SUCCESS, $tester->execute(['format' => 'App\\Export\\Scss']));
        self::assertSame(Command::SUCCESS, $tester->execute(['format' => 'app\\export\\scss']));
        self::assertSame('custom', $tester->getDisplay());
    }

    public function testAnApplicationCssGeneratorIsUsedAsIs(): void
    {
        $generator = new class implements GeneratorInterface {
            public function generate(array $resolvedTokens, array $context = []): string
            {
                return 'custom css';
            }
        };
        $tester = new CommandTester(new ExportCommand($this->tokenRegistry, new ServiceLocator(['css' => static fn (): GeneratorInterface => $generator])));

        $tester->execute(['format' => 'css']);

        self::assertSame('custom css', $tester->getDisplay());
    }

    public function testCompletesFormats(): void
    {
        $tester = new CommandCompletionTester(self::command($this->tokenRegistry));

        self::assertContains('css', $tester->complete(['']));
        self::assertContains('dtcg', $tester->complete(['']));
    }

    private function resolverTester(): CommandTester
    {
        return new CommandTester(self::command(Registries::fromFiles(resolverPath: $this->writeSchemeResolver())));
    }

    private function writeSchemeResolver(): string
    {
        return $this->directory->write('theme.resolver.json', [
            'version' => '2025.10',
            'modifiers' => [
                'scheme' => [
                    'contexts' => [
                        'light' => [['theme' => ['$type' => 'number', '$value' => 1]]],
                        'dark' => [['theme' => ['$type' => 'number', '$value' => 2]]],
                    ],
                    'default' => 'light',
                ],
            ],
            'resolutionOrder' => [['$ref' => '#/modifiers/scheme']],
        ]);
    }

    private static function command(TokenRegistry $registry): ExportCommand
    {
        $generators = new ServiceLocator([
            'dtcg' => static fn (): GeneratorInterface => new DtcgGenerator(),
            'css' => static fn (): GeneratorInterface => new CssGenerator(),
            'javascript' => static fn (): GeneratorInterface => new JavaScriptGenerator(),
        ]);

        return new ExportCommand($registry, $generators);
    }
}
