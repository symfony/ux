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
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\UX\DesignTokens\Command\DebugTokensCommand;
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Tests\Fixtures\Registries;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;
use Symfony\UX\DesignTokens\TokenRegistry;

#[CoversClass(DebugTokensCommand::class)]
final class DebugTokensCommandTest extends TestCase
{
    private ConfiguredTokenResolver $resolver;
    private TokenRegistry $registry;

    protected function setUp(): void
    {
        $this->resolver = new ConfiguredTokenResolver(new ArrayDocumentLoader(['memory.tokens.json' => json_decode('{"color":{"brand":{"$type":"color","$description":"Brand color","$value":{"colorSpace":"srgb","components":[0.2,0.4,0.8]}}},"spacing":{"sm":{"$type":"dimension","$value":{"value":4,"unit":"px"}}}}', true, 512, \JSON_THROW_ON_ERROR)]), ['memory.tokens.json']);
        $this->registry = new TokenRegistry($this->resolver);
    }

    public function testDisplaysResolvedTokensAsTable(): void
    {
        $tester = new CommandTester(new DebugTokensCommand($this->registry));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('color.brand', $tester->getDisplay());
        self::assertStringContainsString('Brand color', $tester->getDisplay());
        self::assertStringContainsString('spacing.sm', $tester->getDisplay());
    }

    public function testFiltersByTokenOrGroupAndReportsJson(): void
    {
        $tester = new CommandTester(new DebugTokensCommand($this->registry));
        $tester->execute(['path' => 'color', '--format' => 'json']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $result = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('color', $result['tokens']['color.brand']['type']);
        self::assertSame([0.2, 0.4, 0.8], $result['tokens']['color.brand']['value']['components']);
        self::assertArrayNotHasKey('spacing.sm', $result['tokens']);

        $tester->execute(['path' => 'spacing.sm']);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('spacing.sm', $tester->getDisplay());
    }

    public function testReportsMissingPathAndEmptyRegistry(): void
    {
        $tester = new CommandTester(new DebugTokensCommand($this->registry));
        $tester->execute(['path' => 'missing']);
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No design tokens found', $tester->getDisplay());

        $tester = new CommandTester(new DebugTokensCommand(new TokenRegistry()));
        $tester->execute([]);
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No design tokens are configured', $tester->getDisplay());
    }

    public function testReportsRegistryFailureAndInvalidInput(): void
    {
        $tester = new CommandTester(new DebugTokensCommand(Registries::fromFiles(paths: ['/missing.tokens.json'])));
        $tester->execute([]);
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('not found', $tester->getDisplay());

        $tester = new CommandTester(new DebugTokensCommand($this->registry));
        $tester->execute(['--format' => 'xml']);
        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertStringContainsString('--format', $tester->getDisplay());

        $tester->execute(['path' => ['invalid']]);
        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertStringContainsString('must be a string', $tester->getDisplay());
    }

    public function testSourcesNamesTheFileThatWonAndTheOnesItReplaced(): void
    {
        $directory = new TemporaryDirectory();
        $base = $directory->write('base.tokens.json', '{"color":{"accent":{"$type":"color","$value":{"colorSpace":"srgb","components":[0,0,1]}}}}');
        $brand = $directory->write('brand.tokens.json', '{"color":{"accent":{"$type":"color","$value":{"colorSpace":"srgb","components":[1,0,0]}}}}');

        try {
            $resolver = new ConfiguredTokenResolver(new JsonDocumentLoader(), [$base, $brand]);
            $tester = new CommandTester(new DebugTokensCommand(new TokenRegistry($resolver), $resolver));
            $tester->execute(['--sources' => true, '--format' => 'json']);

            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            $accent = json_decode($tester->getDisplay(), true)['tokens']['color.accent'];

            self::assertSame($brand, $accent['source']);
            self::assertSame([$base], $accent['overrides']);
        } finally {
            $directory->remove();
        }
    }

    public function testSourcesAddsTwoColumnsToTheTable(): void
    {
        $tester = new CommandTester(new DebugTokensCommand($this->registry, $this->resolver));
        $tester->execute(['--sources' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Source', $tester->getDisplay());
        self::assertStringContainsString('Replaced', $tester->getDisplay());
    }

    public function testProvenanceIsOffByDefault(): void
    {
        $tester = new CommandTester(new DebugTokensCommand($this->registry));
        $tester->execute([]);

        self::assertStringNotContainsString('Source', $tester->getDisplay());
        self::assertStringNotContainsString('Replaced', $tester->getDisplay());
    }
}
