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
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Command\LintDesignTokensCommand;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\Registries;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;
use Symfony\UX\DesignTokens\TokenRegistry;
use Symfony\UX\DesignTokens\Validation\DtcgValidator;
use Symfony\UX\DesignTokens\Validation\Normalizer;

#[CoversClass(LintDesignTokensCommand::class)]
final class LintDesignTokensCommandTest extends TestCase
{
    private TemporaryDirectory $directory;
    private string $valid;

    protected function setUp(): void
    {
        $this->directory = new TemporaryDirectory();
        $this->valid = $this->directory->write('valid.tokens.json', self::tokens());
        $this->directory->write('nested/invalid.tokens.json', '{"bad":{"$type":"dimension","$value":"12px"}}');
        $this->directory->write('ignored.json', '{}');
    }

    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testLintsConfiguredFilesByDefault(): void
    {
        $tester = $this->tester([$this->valid]);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('The design token document is valid', $tester->getDisplay());
    }

    public function testLintsDirectoriesAndReportsJson(): void
    {
        $tester = $this->tester();
        $tester->execute(['filename' => [$this->directory->path()], '--format' => 'json']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        $result = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertFalse($result['valid']);
        self::assertCount(2, $result['files']);
        self::assertStringContainsString('structured dimension', $result['files'][0]['error'] ?? $result['files'][1]['error']);
    }

    public function testReportsGithubAnnotationsAndEscapesValues(): void
    {
        $path = $this->directory->write('bad,file.tokens.json', '{');

        $tester = $this->tester();
        $tester->execute(['filename' => [$path], '--format' => 'github']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('::error file=', $tester->getDisplay());
        self::assertStringContainsString('bad%2Cfile.tokens.json', $tester->getDisplay());
        self::assertStringContainsString('::Invalid JSON in "', $tester->getDisplay());
        self::assertStringContainsString('": Syntax error', $tester->getDisplay());
    }

    public function testSupportsStdinAndReportsReadFailure(): void
    {
        $tester = $this->stdinTester(self::tokens());
        $tester->execute(['filename' => ['-']]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $tester = $this->stdinTester('');
        $tester->execute(['filename' => ['-']]);
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Could not read', $tester->getDisplay());
    }

    public function testValidatesTheConfiguredResolver(): void
    {
        $tester = $this->tester(resolver: \dirname(__DIR__, 2).'/Integration/Fixtures/theme.resolver.json');

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    /**
     * @param array<string, mixed> $input a "filename" list names entries of the temporary directory
     */
    #[DataProvider('rejectedRunProvider')]
    public function testRejectsARunItCannotLint(array $input, int $status, string $message): void
    {
        $this->directory->mkdir('empty');
        if (\is_array($input['filename'] ?? null)) {
            $input['filename'] = array_map($this->directory->path(...), $input['filename']);
        }

        $tester = $this->tester();

        self::assertSame($status, $tester->execute($input));
        self::assertStringContainsString($message, $tester->getDisplay());
    }

    /** @return iterable<string, array{array<string, mixed>, int, string}> */
    public static function rejectedRunProvider(): iterable
    {
        yield 'no file given or configured' => [[], Command::INVALID, 'No design token files'];
        yield 'unknown format' => [['filename' => ['valid.tokens.json'], '--format' => 'xml'], Command::INVALID, '--format'];
        yield 'filename that is not a list' => [['filename' => 'not-a-list'], Command::INVALID, 'must be a list'];
        yield 'missing path' => [['filename' => ['missing']], Command::FAILURE, 'path not found'];
        yield 'empty directory' => [['filename' => ['empty']], Command::FAILURE, 'No .tokens.json'];
        yield 'file without the .tokens.json suffix' => [['filename' => ['ignored.json']], Command::FAILURE, 'Expected a .tokens.json'];
    }

    public function testFixRewritesADocumentInItsNormalizedForm(): void
    {
        $compact = $this->directory->write('compact.tokens.json', self::tokens());

        $tester = $this->tester();
        self::assertSame(Command::SUCCESS, $tester->execute(['filename' => [$compact], '--fix' => true]));

        $rewritten = (string) file_get_contents($compact);
        self::assertNotSame(self::tokens(), $rewritten);
        self::assertStringContainsString("\n", $rewritten);
        self::assertStringContainsString('Normalized '.$compact, $tester->getDisplay());
        self::assertSame(json_decode(self::tokens(), true), json_decode($rewritten, true));

        $second = $this->tester();
        $second->execute(['filename' => [$compact], '--fix' => true]);
        self::assertStringNotContainsString('Normalized', $second->getDisplay());
        self::assertSame($rewritten, file_get_contents($compact));
    }

    public function testWithoutFixANonNormalizedDocumentIsStillValid(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['filename' => [$this->valid]]));
        self::assertStringNotContainsString('Normalized', $tester->getDisplay());
        self::assertSame(self::tokens(), file_get_contents($this->valid));
    }

    public function testFixOnStdinWritesTheNormalizedDocumentToOutput(): void
    {
        $tester = $this->stdinTester(self::tokens());

        self::assertSame(Command::SUCCESS, $tester->execute(['filename' => ['-'], '--fix' => true]));
        self::assertStringContainsString('"color"', $tester->getDisplay());
        self::assertStringNotContainsString('Normalized -', $tester->getDisplay());
    }

    public function testLintingTheConfiguredFilesAlsoChecksTheApplicationResolution(): void
    {
        $registry = Registries::fromFiles(paths: [$this->valid]);
        $tester = $this->tester([$this->valid], registry: $registry);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('configured resolution holds', $tester->getDisplay());

        $json = $this->tester([$this->valid], registry: Registries::fromFiles(paths: [$this->valid]));
        $json->execute(['--format' => 'json']);
        self::assertSame(
            ['valid' => true, 'tokens' => 1, 'error' => null],
            json_decode($json->getDisplay(), true)['resolution'],
        );
    }

    /** @param array<string, mixed> $input */
    #[DataProvider('brokenResolutionOutputProvider')]
    public function testAResolutionThatFailsIsReportedEvenWhenEveryDocumentIsValid(array $input, string $expected): void
    {
        $tester = $this->danglingTester();

        self::assertSame(Command::FAILURE, $tester->execute($input));
        self::assertStringContainsString($expected, $tester->getDisplay());
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function brokenResolutionOutputProvider(): iterable
    {
        yield 'text' => [[], 'The configured design tokens do not resolve'];
        yield 'GitHub annotation' => [['--format' => 'github'], '::error::'];
    }

    /** @param list<string> $configured */
    private function tester(array $configured = [], ?string $resolver = null, ?TokenRegistry $registry = null): CommandTester
    {
        $validator = new DtcgValidator(new TokenTreeBuilder(new JsonDocumentLoader()));

        return new CommandTester(new LintDesignTokensCommand($validator, new Normalizer($validator), $registry, $configured, $resolver));
    }

    private function stdinTester(string $stdin): CommandTester
    {
        $tester = $this->tester();
        $tester->setInputs([$stdin]);

        return $tester;
    }

    private function danglingTester(): CommandTester
    {
        $dangling = $this->directory->write('dangling.tokens.json', '{"color":{"alias":{"$type":"color","$value":"{color.missing}"}}}');

        return $this->tester([$dangling], registry: Registries::fromFiles(paths: [$dangling]));
    }

    private static function tokens(): string
    {
        return '{"color":{"brand":{"$type":"color","$value":{"colorSpace":"srgb","components":[0.2,0.4,0.8]}}}}';
    }

    public function testADocumentALintedResolverCompletesIsLintedAndFixed(): void
    {
        $theme = $this->writeLayeredTheme('{"color":{"text":{"$type":"color","$value":"{color.brand}"}}}');

        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute(['filename' => [$theme], '--fix' => true]), $tester->getDisplay());
        self::assertStringContainsString('Normalized '.$theme.'/foundation.tokens.json', $tester->getDisplay());
        self::assertStringContainsString("\n", (string) file_get_contents($theme.'/foundation.tokens.json'));
    }

    public function testTheResolverReportsAReferenceNoSourceDefines(): void
    {
        $theme = $this->writeLayeredTheme('{"color":{"text":{"$type":"color","$value":"{color.brnad}"}}}');

        $tester = $this->tester();
        $tester->execute(['filename' => [$theme], '--format' => 'json']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        $files = array_column(json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR)['files'], null, 'file');
        self::assertTrue($files[$theme.'/foundation.tokens.json']['valid']);
        self::assertStringContainsString('{color.brnad}', (string) $files[$theme.'/theme.resolver.json']['error']);
    }

    public function testAnInvalidValueALaterSourceOverridesIsStillReported(): void
    {
        $theme = $this->writeLayeredTheme('{"color":{"brand":{"$type":"dimension","$value":"12px"}}}');

        $tester = $this->tester();
        $tester->execute(['filename' => [$theme], '--format' => 'json']);

        $files = array_column(json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR)['files'], null, 'file');
        self::assertStringContainsString('structured dimension', (string) $files[$theme.'/foundation.tokens.json']['error']);
    }

    public function testADocumentLintedWithoutItsResolverSaysWhatToLint(): void
    {
        $theme = $this->writeLayeredTheme('{"color":{"text":{"$type":"color","$value":"{color.brand}"}}}');

        $tester = $this->tester();

        self::assertSame(Command::FAILURE, $tester->execute(['filename' => [$theme.'/foundation.tokens.json']]));
        self::assertStringContainsString('lint it with that Resolver', $tester->getDisplay());
    }

    private function writeLayeredTheme(string $foundation): string
    {
        $this->directory->write('theme/foundation.tokens.json', $foundation);
        $this->directory->write('theme/brand.tokens.json', '{"color":{"brand":{"$type":"color","$value":{"colorSpace":"srgb","components":[0,0,1]}}}}');
        $this->directory->write('theme/theme.resolver.json', '{"version":"2025.10","sets":{"foundation":{"sources":[{"$ref":"foundation.tokens.json"}]},"brand":{"sources":[{"$ref":"brand.tokens.json"}]}},"resolutionOrder":[{"$ref":"#/sets/foundation"},{"$ref":"#/sets/brand"}]}');

        return $this->directory->path().'/theme';
    }

    public function testFixReportsADocumentItCannotRewrite(): void
    {
        $file = $this->directory->write('locked/theme.tokens.json', self::tokens());
        new Filesystem()->chmod($locked = \dirname($file), 0o500);

        try {
            $tester = $this->tester();

            self::assertSame(Command::FAILURE, $tester->execute(['filename' => [$file], '--fix' => true]));
            self::assertStringContainsString('Could not write the normalized document', $tester->getDisplay());
            self::assertSame(self::tokens(), file_get_contents($file));
        } finally {
            new Filesystem()->chmod($locked, 0o700);
        }
    }

    public function testABrokenResolutionIsReportedInJson(): void
    {
        $tester = $this->danglingTester();

        self::assertSame(Command::FAILURE, $tester->execute(['--format' => 'json']));
        $payload = json_decode($tester->getDisplay(), true);
        self::assertFalse($payload['valid']);
        self::assertFalse($payload['resolution']['valid']);
        self::assertNotNull($payload['resolution']['error']);
    }

    public function testAnUnreadableDocumentIsReported(): void
    {
        $unreadable = $this->directory->write('unreadable.tokens.json', self::tokens());
        new Filesystem()->chmod($unreadable, 0o000);

        $tester = $this->tester();
        $tester->execute(['filename' => [$unreadable]]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    public function testFixingStdinWritesOnlyTheDocumentToStdout(): void
    {
        $tester = $this->stdinTester(self::tokens());
        $tester->execute(['filename' => ['-'], '--fix' => true, '--format' => 'json'], ['capture_stderr_separately' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertArrayHasKey('color', json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR));
        self::assertTrue(json_decode($tester->getErrorOutput(), true, 512, \JSON_THROW_ON_ERROR)['valid']);
    }

    public function testDefaultsToGithubAnnotationsOnGithubActions(): void
    {
        $previous = getenv('GITHUB_ACTIONS');
        putenv('GITHUB_ACTIONS=true');
        $broken = $this->directory->write('broken.tokens.json', '{');

        try {
            $tester = $this->tester();
            $tester->execute(['filename' => [$broken]]);

            self::assertStringContainsString('::error file=', $tester->getDisplay());
        } finally {
            false === $previous ? putenv('GITHUB_ACTIONS') : putenv('GITHUB_ACTIONS='.$previous);
        }
    }
}
