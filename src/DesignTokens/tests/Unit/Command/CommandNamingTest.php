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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\UX\DesignTokens\Command\DebugTokensCommand;
use Symfony\UX\DesignTokens\Command\ExportCommand;
use Symfony\UX\DesignTokens\Command\LintDesignTokensCommand;

final class CommandNamingTest extends TestCase
{
    /** @return iterable<string, array{class-string<Command>, string}> */
    public static function commands(): iterable
    {
        yield 'debug' => [DebugTokensCommand::class, 'debug:design-tokens'];
        yield 'export' => [ExportCommand::class, 'ux:design-tokens:export'];
        yield 'lint' => [LintDesignTokensCommand::class, 'lint:design-tokens'];
    }

    /** @param class-string<Command> $class */
    #[DataProvider('commands')]
    public function testEveryCommandHasOneNameAndNoAlias(string $class, string $name): void
    {
        $attributes = new \ReflectionClass($class)->getAttributes(AsCommand::class);
        self::assertCount(1, $attributes, \sprintf('"%s" should carry one #[AsCommand].', $class));

        $declared = $attributes[0]->getArguments();

        self::assertSame($name, $declared['name'] ?? null);
        self::assertArrayNotHasKey('aliases', $declared);
    }

    public function testEveryCommandInThePackageIsCovered(): void
    {
        $declared = array_map(
            static fn (string $file): string => basename($file, '.php'),
            glob(\dirname(__DIR__, 3).'/src/Command/*Command.php') ?: [],
        );
        $covered = array_map(
            static fn (array $case): string => new \ReflectionClass($case[0])->getShortName(),
            iterator_to_array(self::commands(), false),
        );

        sort($declared);
        sort($covered);

        self::assertSame($declared, $covered, 'A new command must be added to the data provider.');
    }
}
