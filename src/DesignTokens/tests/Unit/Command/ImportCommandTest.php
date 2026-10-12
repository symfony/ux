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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Bridge\Tailwind\ThemeImporter;
use Symfony\UX\DesignTokens\Command\ImportCommand;
use Symfony\UX\DesignTokens\Importer\ImporterInterface;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;

#[CoversClass(ImportCommand::class)]
final class ImportCommandTest extends TestCase
{
    private TemporaryDirectory $directory;
    private string $input;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->directory = new TemporaryDirectory();
        $this->input = $this->directory->write('theme.css', '@theme { --color-brand: #336699; --spacing-md: 1rem; }');
        $this->tester = new CommandTester(self::command());
    }

    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testImportsToStdout(): void
    {
        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        $tokens = json_decode($this->tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('color', $tokens['color']['brand']['$type']);
        self::assertSame('dimension', $tokens['spacing']['md']['$type']);
    }

    public function testImportsToAFile(): void
    {
        $output = $this->directory->path('tokens.json');

        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input, 'output' => $output]);
        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertFileExists($output);
        self::assertStringContainsString('Imported tailwind tokens', $this->tester->getDisplay());
    }

    public function testReportsUnknownFormatInvalidInputAndWriteFailure(): void
    {
        $this->tester->execute(['format' => 'json', 'input' => $this->input]);
        self::assertSame(Command::INVALID, $this->tester->getStatusCode());
        self::assertStringContainsString('Unknown import format "json". Available: tailwind', $this->tester->getDisplay());

        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input.'.missing']);
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('not found', $this->tester->getDisplay());

        $blocker = $this->directory->write('blocker', '');
        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input, 'output' => $blocker.'/tokens.json']);
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('Could not write imported tokens', $this->tester->getDisplay());
    }

    public function testReportsAnUnreadableInput(): void
    {
        new Filesystem()->chmod($this->input, 0o000);

        try {
            $this->tester->execute(['format' => 'tailwind', 'input' => $this->input]);

            self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
            self::assertStringContainsString('Could not read', $this->tester->getDisplay());
        } finally {
            new Filesystem()->chmod($this->input, 0o600);
        }
    }

    public function testAnyImporterJoinsTheCommandUnderItsFormat(): void
    {
        $importer = new class implements ImporterInterface {
            public function import(string $contents, array $context = []): array
            {
                return ['imported' => ['$type' => 'number', '$value' => \strlen($contents)]];
            }
        };
        $tester = new CommandTester(new ImportCommand(new ServiceLocator(['css' => static fn (): ImporterInterface => $importer]), new TokenTreeBuilder()));

        self::assertSame(Command::SUCCESS, $tester->execute(['format' => 'css', 'input' => $this->input]));
        self::assertStringContainsString('"imported"', $tester->getDisplay());
    }

    public function testCompletesFormats(): void
    {
        self::assertSame(['tailwind'], new CommandCompletionTester(self::command())->complete(['']));
    }

    public function testRejectsNonStringArgumentsWhenInvokedProgrammatically(): void
    {
        $this->tester->execute(['format' => [], 'input' => $this->input]);
        self::assertSame(Command::INVALID, $this->tester->getStatusCode());
        self::assertStringContainsString('format must be a string', $this->tester->getDisplay());

        $this->tester->execute(['format' => 'tailwind', 'input' => []]);
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('input path must be a string', $this->tester->getDisplay());

        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input, 'output' => []]);
        self::assertSame(Command::INVALID, $this->tester->getStatusCode());
        self::assertStringContainsString('output path must be a string', $this->tester->getDisplay());
    }

    public function testLogsSkippedVariablesToStderrByVerbosity(): void
    {
        $this->directory->write('theme.css', '@theme { --color-brand: #336699; --animate-spin: spin 1s linear infinite; --spacing-fluid: 1vw; }');

        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input], ['capture_stderr_separately' => true]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertArrayHasKey('color', json_decode($this->tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR));
        self::assertStringContainsString('--spacing-fluid', $this->tester->getErrorOutput());
        self::assertStringNotContainsString('--animate-spin', $this->tester->getErrorOutput());

        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input], ['capture_stderr_separately' => true, 'verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertStringContainsString('--animate-spin', $this->tester->getErrorOutput());
    }

    public function testDoesNotReplaceAnExistingFileWithoutForce(): void
    {
        $output = $this->directory->write('tokens.json', 'kept');

        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input, 'output' => $output]);
        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('--force', $this->tester->getDisplay());
        self::assertSame('kept', file_get_contents($output));

        $this->tester->execute(['format' => 'tailwind', 'input' => $this->input, 'output' => $output, '--force' => true]);
        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertStringContainsString('"brand"', (string) file_get_contents($output));
    }

    private static function command(): ImportCommand
    {
        return new ImportCommand(new ServiceLocator(['tailwind' => static fn (): ImporterInterface => new ThemeImporter()]), new TokenTreeBuilder());
    }
}
