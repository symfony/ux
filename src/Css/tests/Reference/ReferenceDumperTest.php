<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Reference;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Reference\ReferenceDumper;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;
use Symfony\UX\Css\Validation\StyleValidator;

final class ReferenceDumperTest extends TestCase
{
    private const SNAPSHOT = __DIR__.'/../Fixtures/Reference/reference_css.php';
    private const USAGE = __DIR__.'/../Fixtures/Reference/usage.php';

    private const PROJECT = [
        'conditions' => ['expanded' => '&[aria-expanded=true]'],
        'theme' => [
            'breakpoints' => ['sm' => '40rem', 'md' => '48rem'],
            'tokens' => [
                'colors' => ['blue' => ['500' => ['value' => '#3b82f6']]],
                'spacing' => ['sm' => ['value' => '0.5rem'], 'md' => ['value' => '1rem']],
            ],
            'semanticTokens' => [
                'colors' => ['primary' => ['value' => '{colors.blue.500}']],
            ],
        ],
    ];

    public function testTheReferenceMatchesItsSnapshot(): void
    {
        $dumper = new ReferenceDumper(new StyleValidator(Engine::fromProjectConfig(self::PROJECT)));

        $reference = $dumper->dump();

        if (getenv('UX_CSS_UPDATE_SNAPSHOTS')) {
            file_put_contents(self::SNAPSHOT, $reference);
        }
        $this->assertStringEqualsFile(self::SNAPSHOT, $reference);
    }

    public function testTheSameConfigGivesTheSameReference(): void
    {
        $first = new ReferenceDumper(new StyleValidator(Engine::fromProjectConfig(self::PROJECT)));
        $second = new ReferenceDumper(new StyleValidator(Engine::fromProjectConfig(self::PROJECT)));

        $this->assertSame($first->dump(), $second->dump());
    }

    public function testTokenNamesThatPhpDocCannotHoldAreLeftToString(): void
    {
        $dumper = new ReferenceDumper(new StyleValidator(Engine::fromProjectConfig(self::projectWithOddTokenNames())));

        $reference = $dumper->dump();

        $this->assertStringContainsString("'with space'", $reference);
        $this->assertStringContainsString("'2xl'", $reference);
        $this->assertStringNotContainsString("it's", $reference);
        $this->assertStringNotContainsString('back\\slash', $reference);
    }

    public function testPhpStanReadsTheReference(): void
    {
        $directory = TestKernel::temporaryDirectory();
        $dumper = new ReferenceDumper(new StyleValidator(Engine::fromProjectConfig(self::projectWithOddTokenNames())));
        new Filesystem()->dumpFile($directory.'/reference_css.php', $dumper->dump());
        $phpstan = self::phpstan($directory, $directory.'/reference_css.php');

        $phpstan->run();

        $this->assertSame(self::expectedErrorLines(), self::errorLines($phpstan->getOutput()));
    }

    public function testPhpStanReadsTheCodeWithoutTheReference(): void
    {
        $phpstan = self::phpstan(TestKernel::temporaryDirectory(), null);

        $phpstan->run();

        $this->assertNotSame(255, $phpstan->getExitCode(), $phpstan->getErrorOutput());
        $this->assertJson($phpstan->getOutput());
    }

    private static function phpstan(string $directory, ?string $reference): Process
    {
        $scanFiles = null !== $reference ? "    scanFiles:\n        - ".$reference."\n" : '';
        $config = \sprintf(
            "parameters:\n    level: max\n    tmpDir: %s\n    paths:\n        - %s\n%s",
            $directory.'/cache',
            realpath(self::USAGE),
            $scanFiles,
        );
        new Filesystem()->dumpFile($directory.'/phpstan.neon', $config);

        return new Process([
            \PHP_BINARY,
            __DIR__.'/../../vendor/bin/phpstan',
            'analyse',
            '--no-progress',
            '--error-format=json',
            '--configuration='.$directory.'/phpstan.neon',
        ], env: ['SHELL_VERBOSITY' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function projectWithOddTokenNames(): array
    {
        $project = self::PROJECT;
        $project['theme']['tokens']['sizes'] = [
            "it's" => ['value' => '1px'],
            'back\\slash' => ['value' => '2px'],
            'with space' => ['value' => '3px'],
            '2xl' => ['value' => '4px'],
        ];

        return $project;
    }

    /**
     * @return array<int, string> the identifier of each expected error, by line
     */
    private static function expectedErrorLines(): array
    {
        $expected = [];
        foreach (file(self::USAGE) as $index => $line) {
            if (str_ends_with(rtrim($line), '// error')) {
                $expected[$index + 1] = 'argument.type';
            }
        }

        return $expected;
    }

    /**
     * @return array<int, string>
     */
    private static function errorLines(string $output): array
    {
        $report = json_decode($output, true, flags: \JSON_THROW_ON_ERROR);

        $errors = [];
        foreach ($report['files'] as $file) {
            foreach ($file['messages'] as $message) {
                $errors[$message['line']] = $message['identifier'] ?? $message['message'];
            }
        }
        ksort($errors);

        return $errors;
    }
}
