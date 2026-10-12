<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Bridge\Tailwind\ThemeImporter;
use Symfony\UX\DesignTokens\CacheWarmer\DesignTokensCacheWarmer;
use Symfony\UX\DesignTokens\Command\ExportCommand;
use Symfony\UX\DesignTokens\Command\ImportCommand;
use Symfony\UX\DesignTokens\Command\LintDesignTokensCommand;
use Symfony\UX\DesignTokens\TokenRegistry;
use Symfony\UX\DesignTokens\TokenRegistryInterface;
use Symfony\UX\DesignTokens\Twig\DesignTokenExtension;
use Symfony\UX\DesignTokens\UXDesignTokensBundle;
use Symfony\UX\DesignTokens\Validation\DtcgValidator;
use Symfony\UX\DesignTokens\Validation\Normalizer;
use Twig\Environment;

#[CoversClass(UXDesignTokensBundle::class)]
#[CoversClass(TokenRegistry::class)]
#[CoversClass(DesignTokenExtension::class)]
#[CoversClass(ExportCommand::class)]
#[CoversClass(ImportCommand::class)]
#[CoversClass(LintDesignTokensCommand::class)]
#[CoversClass(ThemeImporter::class)]
#[CoversClass(DtcgValidator::class)]
#[CoversClass(Normalizer::class)]
#[CoversClass(DesignTokensCacheWarmer::class)]
final class BundleIntegrationTest extends TestCase
{
    /** @var list<IntegrationTestKernel> */
    private array $kernels = [];

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        foreach ($this->kernels as $kernel) {
            $cacheDir = $kernel->getCacheDir();
            $kernel->shutdown();
            $filesystem->remove($cacheDir);
        }
        $this->kernels = [];
    }

    public function testConfiguredFilesLoadThroughTheRegistryService(): void
    {
        $kernel = $this->boot([
            'paths' => [$this->fixture('app.tokens.json')],
        ]);

        $registry = $kernel->getContainer()->get(TokenRegistryInterface::class);

        self::assertInstanceOf(TokenRegistryInterface::class, $registry);
        self::assertSame('color(srgb 0.2 0.4 0.8)', (string) $registry->get('color.brand'));
    }

    public function testEveryBundleServiceIsInstantiable(): void
    {
        $container = $this->boot([
            'paths' => [$this->fixture('app.tokens.json')],
        ])->getContainer();

        $ids = array_filter($container->getServiceIds(), static fn (string $id): bool => str_starts_with($id, '.ux_design_tokens.'));

        self::assertNotEmpty($ids);
        foreach ($ids as $id) {
            self::assertIsObject($container->get($id), $id);
        }
    }

    public function testResolverSourcesStayInsideTheAllowedRoots(): void
    {
        $filesystem = new Filesystem();
        $outside = sys_get_temp_dir().'/dt-outside-'.bin2hex(random_bytes(4)).'.tokens.json';
        $filesystem->dumpFile($outside, '{"x":{"$type":"number","$value":1}}');
        $directory = $this->fixture('').'escape-'.bin2hex(random_bytes(4));
        $filesystem->dumpFile($directory.'/theme.resolver.json', json_encode([
            'version' => '2025.10',
            'sets' => ['s' => ['sources' => [['$ref' => $outside]]]],
            'resolutionOrder' => [['$ref' => '#/sets/s']],
        ], \JSON_THROW_ON_ERROR));

        try {
            $registry = $this->boot(['resolver' => ['path' => $directory.'/theme.resolver.json']])->getContainer()->get(TokenRegistryInterface::class);
            \assert($registry instanceof TokenRegistryInterface);

            $this->expectExceptionMessage('Refusing to read design token document');
            $registry->all();
        } finally {
            $filesystem->remove([$outside, $directory]);
        }
    }

    public function testConfiguredFileReferencesResolveRelativeToTheirSource(): void
    {
        $kernel = $this->boot([
            'paths' => [$this->fixture('reference.tokens.json')],
        ]);

        $registry = $kernel->getContainer()->get(TokenRegistryInterface::class);

        self::assertSame('color(srgb 0.1 0.3 0.7)', (string) $registry->get('semantic'));
    }

    public function testResolverInputsSelectTheConfiguredContext(): void
    {
        $kernel = $this->boot([
            'resolver' => ['path' => $this->fixture('theme.resolver.json'), 'inputs' => ['theme' => 'dark']],
        ]);

        $registry = $kernel->getContainer()->get(TokenRegistryInterface::class);

        self::assertSame('dark', (string) $registry->get('theme'));
    }

    public function testTheRegistryRejectsAnInvalidDocument(): void
    {
        $kernel = $this->boot([
            'paths' => [\dirname(__DIR__).'/Fixtures/dtcg/format-invalid-legacy-values.tokens.json'],
        ]);

        $registry = $kernel->getContainer()->get(TokenRegistryInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $registry->all();
    }

    public function testTwigTokenOutputIsAutoEscaped(): void
    {
        $kernel = $this->boot([
            'paths' => [$this->fixture('app.tokens.json')],
        ]);
        $twig = $kernel->getContainer()->get('test.twig');
        self::assertInstanceOf(Environment::class, $twig);

        $output = $twig->createTemplate('{{ ux_token("unsafe") }}')->render();

        self::assertSame('&quot;&lt;strong&gt;token&lt;/strong&gt;&quot;', $output);
    }

    public function testExportCommandIsDiscoveredAndUsesConfiguredTokens(): void
    {
        $kernel = $this->boot([
            'paths' => [$this->fixture('app.tokens.json')],
        ]);
        $command = new Application($kernel)->find('ux:design-tokens:export');

        $tester = new CommandTester($command);
        $status = $tester->execute(['format' => 'css']);

        self::assertSame(0, $status);
        self::assertStringContainsString('--dt-color-brand', $tester->getDisplay());
    }

    #[DataProvider('shippedFormats')]
    public function testEveryShippedFormatExportsThroughTheContainer(string $format): void
    {
        $kernel = $this->boot([
            'resolver' => ['path' => \dirname(__DIR__).'/Fixtures/color-scheme/theme.resolver.json'],
        ]);
        $tester = new CommandTester(new Application($kernel)->find('ux:design-tokens:export'));

        self::assertSame(0, $tester->execute(['format' => $format]), $tester->getDisplay());
        self::assertNotSame('', trim($tester->getDisplay()));
    }

    /** @return iterable<string, array{string}> */
    public static function shippedFormats(): iterable
    {
        foreach (['dtcg', 'css', 'javascript', 'design.md', 'tailwind'] as $format) {
            yield $format => [$format];
        }
    }

    public function testADebugKernelServesAStylesheetRebuiltAfterAnEdit(): void
    {
        $directory = sys_get_temp_dir().'/ux-design-tokens-edit-'.bin2hex(random_bytes(4));
        new Filesystem()->mirror(\dirname(__DIR__).'/Fixtures/color-scheme', $directory);
        $config = ['resolver' => ['path' => $directory.'/theme.resolver.json']];
        $cacheDir = sys_get_temp_dir().'/ux-design-tokens-kernel-'.bin2hex(random_bytes(6));

        try {
            self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.2 0.4 0.8);', $this->renderCss($config, $cacheDir));

            $light = $directory.'/light.tokens.json';
            new Filesystem()->dumpFile($light, str_replace('"primary": { "$value": "{color.palette.primary-light}" }', '"primary": { "$value": { "colorSpace": "srgb", "components": [0.9, 0.1, 0.1] } }', (string) file_get_contents($light)));
            new Filesystem()->touch($light, time() + 10);

            self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.9 0.1 0.1);', $this->renderCss($config, $cacheDir));
        } finally {
            new Filesystem()->remove($directory);
        }
    }

    public function testThePreloadScriptHoldsNoStylesheet(): void
    {
        $kernel = $this->boot([
            'resolver' => ['path' => \dirname(__DIR__).'/Fixtures/color-scheme/theme.resolver.json'],
        ]);
        $tester = new CommandTester(new Application($kernel)->find('cache:warmup'));

        self::assertSame(0, $tester->execute([]));
        self::assertFileExists($kernel->getBuildDir().'/ux_design_tokens/tokens.css');

        $preload = glob($kernel->getBuildDir().'/*.preload.php') ?: [];
        self::assertNotSame([], $preload);
        foreach ($preload as $file) {
            self::assertStringNotContainsString('.css', (string) file_get_contents($file));
        }
    }

    public function testTheDarkColorSchemeWorksThroughTheCommandAndTwig(): void
    {
        $kernel = $this->boot([
            'resolver' => ['path' => \dirname(__DIR__).'/Fixtures/color-scheme/theme.resolver.json'],
        ]);

        $command = new CommandTester(new Application($kernel)->find('ux:design-tokens:export'));
        self::assertSame(0, $command->execute(['format' => 'css']));
        self::assertDarkScheme($command->getDisplay());

        $twig = $kernel->getContainer()->get('test.twig');
        self::assertInstanceOf(Environment::class, $twig);
        self::assertDarkScheme($twig->createTemplate('{{ ux_token_css() }}')->render());

        $contextualHtml = $twig->createTemplate('{{ ux_token_css({package: "map"}) }}')->render();
        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.1 0.7 0.5);', $contextualHtml);

        $contextualToken = $twig->createTemplate('{{ ux_token("font.size.body", {surface: "docs"}) }}')->render();
        self::assertSame('1.125rem', $contextualToken);
    }

    public function testTailwindImporterServiceAndCommandAreDiscovered(): void
    {
        $kernel = $this->boot([]);
        $importer = $kernel->getContainer()->get('.ux_design_tokens.importer.tailwind');
        self::assertInstanceOf(ThemeImporter::class, $importer);
        self::assertSame('color', $importer->import('@theme { --color-brand: #336699; }')['color']['brand']['$type']);

        $command = new Application($kernel)->find('ux:design-tokens:import');
        self::assertSame('ux:design-tokens:import', $command->getName());
    }

    public function testLintServicesAndCommandsAreDiscovered(): void
    {
        $kernel = $this->boot([
            'paths' => [$this->fixture('app.tokens.json')],
        ]);
        $container = $kernel->getContainer();
        self::assertInstanceOf(DtcgValidator::class, $container->get('.ux_design_tokens.validator'));

        $application = new Application($kernel);
        $lint = new CommandTester($application->find('lint:design-tokens'));
        self::assertSame(0, $lint->execute([]));
        self::assertStringContainsString('document is valid', $lint->getDisplay());

        self::assertStringContainsString('The configured resolution holds', $lint->getDisplay());

        $debug = new CommandTester($application->find('debug:design-tokens'));
        self::assertSame(0, $debug->execute(['path' => 'color.brand']));
        self::assertStringContainsString('color.brand', $debug->getDisplay());
    }

    public function testTheColorSchemeModifierIsConfigurable(): void
    {
        $kernel = $this->boot([
            'resolver' => ['path' => \dirname(__DIR__).'/Fixtures/color-scheme/named-theme.resolver.json'],
            'color_scheme' => ['modifier' => 'mode', 'light' => 'day', 'dark' => 'night'],
        ]);

        $twig = $kernel->getContainer()->get('test.twig');
        self::assertInstanceOf(Environment::class, $twig);
        self::assertDarkScheme($twig->createTemplate('{{ ux_token_css() }}')->render());

        $command = new CommandTester(new Application($kernel)->find('ux:design-tokens:export'));
        self::assertSame(0, $command->execute(['format' => 'css']));
        self::assertDarkScheme($command->getDisplay());
    }

    public function testCssPrefixIsApplicationOwned(): void
    {
        $kernel = $this->boot([
            'resolver' => ['path' => \dirname(__DIR__).'/Fixtures/color-scheme/theme.resolver.json'],
            'css_prefix' => 'my',
        ]);

        $command = new CommandTester(new Application($kernel)->find('ux:design-tokens:export'));
        self::assertSame(0, $command->execute(['format' => 'css']));
        self::assertStringContainsString('--my-color-action-primary:', $command->getDisplay());

        $twig = $kernel->getContainer()->get('test.twig');
        self::assertInstanceOf(Environment::class, $twig);
        $html = $twig->createTemplate('{{ ux_token_css() }}')->render();
        self::assertStringContainsString('--my-color-action-primary:', $html);
    }

    public function testTheWarmedStylesheetIsServedFromTheBuildDirectory(): void
    {
        $kernel = $this->boot([
            'resolver' => ['path' => \dirname(__DIR__).'/Fixtures/color-scheme/theme.resolver.json'],
        ]);

        $warmer = $kernel->getContainer()->get('test.cache_warmer');
        self::assertInstanceOf(DesignTokensCacheWarmer::class, $warmer);
        $warmer->warmUp($kernel->getCacheDir(), $kernel->getBuildDir());

        $stylesheet = $kernel->getBuildDir().'/ux_design_tokens/tokens.css';
        self::assertFileExists($stylesheet);
        self::assertDarkScheme((string) file_get_contents($stylesheet));

        $twig = $kernel->getContainer()->get('test.twig');
        self::assertInstanceOf(Environment::class, $twig);
        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.2 0.4 0.8);', $twig->createTemplate('{{ ux_token_css() }}')->render());
    }

    private function boot(array $config): IntegrationTestKernel
    {
        $cacheDir = sys_get_temp_dir().'/ux-design-tokens-kernel-'.bin2hex(random_bytes(6));
        $kernel = new IntegrationTestKernel($config, $cacheDir);
        $this->kernels[] = $kernel;
        $kernel->boot();

        return $kernel;
    }

    /** @param array<string, mixed> $config */
    private function renderCss(array $config, string $cacheDir): string
    {
        $kernel = new IntegrationTestKernel($config, $cacheDir, true);
        $this->kernels[] = $kernel;
        $kernel->boot();
        $twig = $kernel->getContainer()->get('test.twig');
        \assert($twig instanceof Environment);

        return $twig->createTemplate('{{ ux_token_css() }}')->render();
    }

    private static function assertDarkScheme(string $css): void
    {
        [$root, $dark] = explode('@media (prefers-color-scheme: dark)', $css, 2) + [1 => ''];

        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.2 0.4 0.8);', $root);
        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.5 0.6 0.9);', $dark);
        self::assertStringNotContainsString('--dt-dimension-spacing-md', $dark);
    }

    private function fixture(string $name): string
    {
        return __DIR__.'/Fixtures/'.$name;
    }
}
