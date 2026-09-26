<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Resolver\DocumentLoaderInterface;
use Symfony\UX\DesignTokens\Resolver\TokenResolverInterface;
use Symfony\UX\DesignTokens\TokenRegistry;
use Symfony\UX\DesignTokens\TokenRegistryInterface;
use Symfony\UX\DesignTokens\UXDesignTokensBundle;
use Symfony\UX\DesignTokens\Validation\DtcgValidator;

#[CoversClass(UXDesignTokensBundle::class)]
final class DesignTokensExtensionTest extends TestCase
{
    public function testPathsResolveContainerParameters(): void
    {
        $container = $this->createContainer();
        $container->setParameter('app.tokens_dir', '/srv/tokens');
        $this->load(['paths' => ['%app.tokens_dir%/a.tokens.json', 'relative/b.tokens.json']], $container);

        self::assertSame(['/srv/tokens/a.tokens.json', Path::join(sys_get_temp_dir(), 'relative/b.tokens.json')], $container->getParameter('.ux_design_tokens.paths'));
    }

    /** @param array<string, mixed> $expected */
    #[DataProvider('parameters')]
    public function testExposesTheConfigurationAsParameters(array $config, array $expected): void
    {
        $container = $this->load($config);

        foreach ($expected as $name => $value) {
            self::assertSame($value, $container->getParameter($name));
        }
    }

    /** @return iterable<string, array{array<string, mixed>, array<string, mixed>}> */
    public static function parameters(): iterable
    {
        yield 'default paths' => [[], ['.ux_design_tokens.paths' => []]];
        yield 'default resolver' => [[], ['.ux_design_tokens.resolver_path' => null, '.ux_design_tokens.resolver_inputs' => []]];
        yield 'custom paths' => [['paths' => ['/tokens/base.json']], ['.ux_design_tokens.paths' => ['/tokens/base.json']]];
        yield 'custom resolver' => [
            ['resolver' => ['path' => 'theme.resolver.json', 'inputs' => ['theme' => 'dark']]],
            ['.ux_design_tokens.resolver_path' => sys_get_temp_dir().'/theme.resolver.json', '.ux_design_tokens.resolver_inputs' => ['theme' => 'dark']],
        ];
        yield 'default color scheme' => [[], ['.ux_design_tokens.color_scheme.modifier' => 'scheme', '.ux_design_tokens.color_scheme.light' => 'light', '.ux_design_tokens.color_scheme.dark' => 'dark']];
        yield 'custom color scheme' => [
            ['color_scheme' => ['modifier' => 'mode', 'light' => 'day', 'dark' => 'night']],
            ['.ux_design_tokens.color_scheme.modifier' => 'mode', '.ux_design_tokens.color_scheme.light' => 'day', '.ux_design_tokens.color_scheme.dark' => 'night'],
        ];
        yield 'paths built from an environment variable' => [
            ['paths' => ['%env(TOKENS_FILE)%'], 'resolver' => ['path' => '%env(resolve:RESOLVER_FILE)%']],
            ['.ux_design_tokens.paths' => ['%env(TOKENS_FILE)%'], '.ux_design_tokens.resolver_path' => '%env(resolve:RESOLVER_FILE)%'],
        ];
    }

    public function testDeclaresItsOwnCachePoolWithFrameworkBundle(): void
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.bundles', ['FrameworkBundle' => FrameworkBundle::class]);
        $this->load([], $container);

        $pool = $container->getDefinition('.ux_design_tokens.cache');
        self::assertInstanceOf(ChildDefinition::class, $pool);
        self::assertSame('cache.system', $pool->getParent());
        self::assertTrue($pool->hasTag('cache.pool'));
    }

    public function testResolvesWithoutACachePoolWithoutFrameworkBundle(): void
    {
        $container = $this->load();

        self::assertFalse($container->has('.ux_design_tokens.cache'));
        self::assertSame(ContainerInterface::NULL_ON_INVALID_REFERENCE, $container->getDefinition('.ux_design_tokens.resolver.configured')->getArgument(3)->getInvalidBehavior());
    }

    public function testTheTwigHelpersNeedTwigBundle(): void
    {
        $container = $this->createContainer();
        $container->setParameter('kernel.bundles', []);
        $this->load([], $container);

        self::assertFalse($container->has('.ux_design_tokens.twig_runtime'));
    }

    public function testExposesTheConsumableServicesUnderTheirClassName(): void
    {
        $container = $this->load();

        foreach ([TokenRegistryInterface::class, TokenResolverInterface::class, DocumentLoaderInterface::class] as $id) {
            self::assertTrue($container->hasAlias($id), \sprintf('"%s" should be aliased.', $id));
        }
        foreach ([TokenRegistry::class, DtcgValidator::class] as $id) {
            self::assertFalse($container->hasAlias($id), \sprintf('"%s" is internal and should not be aliased.', $id));
        }
    }

    /** @param list<array<string, mixed>> $attributes */
    #[DataProvider('builtInTaggedServiceProvider')]
    public function testRegistersABuiltInTaggedService(string $id, string $tag, array $attributes): void
    {
        $container = $this->load();

        self::assertTrue($container->has($id));
        self::assertSame($attributes, $container->getDefinition($id)->getTag($tag));
    }

    /** @return iterable<string, array{string, string, list<array<string, mixed>>}> */
    public static function builtInTaggedServiceProvider(): iterable
    {
        yield 'Twig extension' => ['.ux_design_tokens.twig_extension', 'twig.extension', [[]]];
        yield 'Twig runtime' => ['.ux_design_tokens.twig_runtime', 'twig.runtime', [[]]];
        yield 'AssetMapper compile listener' => ['.ux_design_tokens.asset_compile_listener', 'kernel.event_listener', [['event' => PreAssetsCompileEvent::class, 'method' => '__invoke']]];
    }

    /** @param class-string $class */
    #[DataProvider('autoconfiguredImplementationProvider')]
    public function testTagsAnyImplementationOfAnExtensionPoint(string $class, string $tag): void
    {
        $container = $this->load();

        $container->register('app.extension_point', $class)
            ->setAutoconfigured(true)
            ->setPublic(true);
        new UXDesignTokensBundle()->build($container);
        $container->compile();

        self::assertTrue($container->getDefinition('app.extension_point')->hasTag($tag));
    }

    /** @return iterable<string, array{class-string, string}> */
    public static function autoconfiguredImplementationProvider(): iterable
    {
        yield 'generator for the export command' => [ScssGeneratorStub::class, 'ux_design_tokens.generator'];
    }

    public function testKeepsInternalServicesOutOfReach(): void
    {
        $container = $this->load();

        foreach ($container->getDefinitions() as $id => $definition) {
            if (str_contains($id, 'DesignTokens')) {
                self::assertStringStartsWith('.ux_design_tokens.', $id);
            }
        }
    }

    public function testRegistersOnlyConfiguredFilesAsContainerResources(): void
    {
        $tokenPath = \dirname(__DIR__, 2).'/Fixtures/dtcg/format-valid.tokens.json';
        $resolverPath = \dirname(__DIR__, 2).'/Fixtures/dtcg/resolver-valid.resolver.json';

        $container = $this->load(['paths' => [$tokenPath], 'resolver' => ['path' => $resolverPath]]);

        $fileResources = array_map(
            static fn (FileResource $resource): string => $resource->getResource(),
            self::resources($container, FileResource::class),
        );
        self::assertContains($tokenPath, $fileResources);
        self::assertContains($resolverPath, $fileResources);

        self::assertSame([], self::resources($container, DirectoryResource::class));
    }

    public function testFingerprintsTheConfigurationTheStylesheetsDependOn(): void
    {
        $fingerprint = fn (array $config): string => $this->load($config)->getDefinition('.ux_design_tokens.stylesheet_cache')->getArgument(6);

        $default = $fingerprint(['paths' => ['/tokens/a.tokens.json']]);

        self::assertSame($default, $fingerprint(['paths' => ['/tokens/a.tokens.json']]));
        self::assertNotSame($default, $fingerprint(['paths' => ['/tokens/a.tokens.json'], 'css_prefix' => 'app']));
        self::assertNotSame($default, $fingerprint(['paths' => ['/tokens/a.tokens.json'], 'color_scheme' => ['modifier' => 'mode']]));
        self::assertNotSame($default, $fingerprint(['paths' => ['/tokens/a.tokens.json', '/tokens/b.tokens.json']]));
    }

    public function testMissingConfiguredFileIsTrackedForCacheInvalidation(): void
    {
        $path = sys_get_temp_dir().'/missing-design-tokens-'.bin2hex(random_bytes(6)).'.tokens.json';
        $container = $this->load(['paths' => [$path]]);

        $resources = array_values(array_filter(
            self::resources($container, FileExistenceResource::class),
            static fn (FileExistenceResource $resource): bool => $resource->getResource() === $path,
        ));
        self::assertCount(1, $resources);
        self::assertTrue($resources[0]->isFresh(time()));
    }

    public function testPathsBuiltFromAnEnvironmentVariableAreNotTrackedAsContainerResources(): void
    {
        $container = $this->load(['paths' => ['%env(TOKENS_FILE)%']]);

        foreach ($container->getResources() as $resource) {
            self::assertStringNotContainsString('env(', (string) $resource);
        }
    }

    public function testAPathCarryingAResolvedEnvPlaceholderIsPassedThroughUntouched(): void
    {
        $bag = new EnvPlaceholderParameterBag();
        $placeholder = $bag->get('env(TOKENS_FILE)');
        self::assertIsString($placeholder);

        $container = new ContainerBuilder($bag);
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        $container->setParameter('kernel.debug', true);

        $this->load(['paths' => [$placeholder]], $container);

        self::assertSame([$placeholder], $container->getParameter('.ux_design_tokens.paths'));
    }

    public function testNothingOutsideConfigTwigTouchesTwig(): void
    {
        $root = \dirname(__DIR__, 3);
        $offenders = [];
        foreach (['config/services.php', 'config/asset_mapper.php'] as $file) {
            if (str_contains((string) file_get_contents($root.'/'.$file), 'Twig')) {
                $offenders[] = $file;
            }
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src')) as $php) {
            if (!$php->isFile() || 'php' !== $php->getExtension()) {
                continue;
            }
            $path = str_replace($root.'/', '', $php->getPathname());
            if (str_starts_with($path, 'src/Twig/')) {
                continue;
            }
            if (preg_match('/^use Twig\\\\/m', (string) file_get_contents($php->getPathname()))) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders);
    }

    public function testTheRenderedStylesheetsAreMappedForAssetMapper(): void
    {
        $container = $this->createContainer();
        $bundle = new UXDesignTokensBundle();

        $instanceof = [];
        $bundle->prependExtension(new ContainerConfigurator($container, new PhpFileLoader($container, new FileLocator()), $instanceof, __FILE__, __FILE__), $container);

        $directory = sys_get_temp_dir().'/'.StylesheetCache::DIRECTORY;
        self::assertSame(
            [$directory => UXDesignTokensBundle::ASSET_NAMESPACE],
            $container->getExtensionConfig('framework')[0]['asset_mapper']['paths'],
        );
        self::assertDirectoryExists($directory);
    }

    public function testAllowedRootsCoverTheProjectAndEveryConfiguredSourceDirectory(): void
    {
        $container = $this->load(['paths' => [\dirname(__DIR__, 2).'/Fixtures/base.tokens.json']]);

        $roots = $container->getParameter('.ux_design_tokens.allowed_roots');
        self::assertIsArray($roots);
        self::assertContains(realpath(\dirname(__DIR__, 2).'/Fixtures'), $roots);
        self::assertContains(realpath(sys_get_temp_dir()), $roots);
    }

    public function testTheResolverIsNotSharedBetweenItsConsumers(): void
    {
        $container = $this->load();

        self::assertFalse($container->getDefinition('.ux_design_tokens.token_tree_builder')->isShared());
    }

    /**
     * Loads the extension with $config, into a fresh test container unless one is given.
     *
     * @param array<string, mixed> $config
     */
    private function load(array $config = [], ?ContainerBuilder $container = null): ContainerBuilder
    {
        $container ??= $this->createContainer();
        $extension = new UXDesignTokensBundle()->getContainerExtension();
        self::assertNotNull($extension);
        $extension->load([] === $config ? [] : [$config], $container);

        return $container;
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.bundles', ['TwigBundle' => TwigBundle::class]);
        $container->setParameter('kernel.bundles_metadata', [
            'FrameworkBundle' => ['path' => \dirname(__DIR__, 3).'/vendor/symfony/framework-bundle'],
            'AssetMapperBundle' => ['path' => \dirname(__DIR__, 3).'/vendor/symfony/asset-mapper'],
        ]);

        return $container;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    private static function resources(ContainerBuilder $container, string $class): array
    {
        return array_values(array_filter($container->getResources(), static fn (object $resource): bool => $resource instanceof $class));
    }
}

final class ScssGeneratorStub implements GeneratorInterface
{
    public function generate(array $resolvedTokens, array $context = []): string
    {
        return '';
    }
}
