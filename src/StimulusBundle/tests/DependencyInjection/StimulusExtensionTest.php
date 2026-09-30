<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\StimulusBundle\AssetMapper\ControllersMapGenerator;
use Symfony\UX\StimulusBundle\DependencyInjection\StimulusExtension;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class StimulusExtensionTest extends TestCase
{
    public function testPrependsPathsWithAssetMapperBundle(): void
    {
        $config = $this->prepend([
            'FrameworkBundle' => ['path' => __DIR__],
            'AssetMapperBundle' => ['path' => __DIR__],
        ]);

        self::assertCount(1, $config);
        self::assertSame('@symfony/stimulus-bundle', array_values($config[0]['asset_mapper']['paths'])[0]);
    }

    public function testPrependsPathsWithLegacyFrameworkBundle(): void
    {
        $filesystem = new Filesystem();
        $directory = sys_get_temp_dir().'/stimulus-framework-'.bin2hex(random_bytes(6));
        $filesystem->dumpFile($directory.'/Resources/config/asset_mapper.php', '<?php');

        try {
            $config = $this->prepend(['FrameworkBundle' => ['path' => $directory]]);

            self::assertSame('@symfony/stimulus-bundle', array_values($config[0]['asset_mapper']['paths'])[0]);
        } finally {
            $filesystem->remove($directory);
        }
    }

    /**
     * @param array<string, array{path: string}> $bundles
     */
    #[DataProvider('provideUnavailableAssetMapper')]
    public function testDoesNotPrependWithoutAssetMapper(array $bundles): void
    {
        self::assertSame([], $this->prepend($bundles));
    }

    /**
     * @return iterable<string, array{array<string, array{path: string}>}>
     */
    public static function provideUnavailableAssetMapper(): iterable
    {
        yield 'no framework bundle' => [[]];
        yield 'no asset mapper configuration' => [['FrameworkBundle' => ['path' => __DIR__]]];
    }

    public function testLoadWithoutApplications(): void
    {
        $container = $this->load([
            'controller_paths' => ['/global/controllers'],
            'controllers_json' => '/global/controllers.json',
        ]);

        self::assertSame([], $container->getDefinition('stimulus.asset_mapper.loader_javascript_compiler')->getArgument(2));

        $generatorArguments = $container->getDefinition('stimulus.asset_mapper.controllers_map_generator')->getArguments();
        self::assertCount(5, $generatorArguments);
        self::assertSame(['/global/controllers'], $generatorArguments[2]);
        self::assertSame('/global/controllers.json', $generatorArguments[3]);
    }

    public function testLoadRegistersApplications(): void
    {
        $container = $this->load([
            'controller_paths' => ['/global/controllers'],
            'controllers_json' => '/global/controllers.json',
            'applications' => [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
                    'controller_paths' => ['/admin/controllers'],
                    'include_global_paths' => false,
                    'controllers_json' => '%stimulus_test.fixtures_dir%/assets/admin/controllers.json',
                ],
                'front-office_2' => [
                    'loader' => '%stimulus_test.fixtures_dir%/assets/front/stimulus_loader.js',
                    'controller_paths' => ['/front/controllers'],
                ],
            ],
        ]);

        $admin = $container->getDefinition('stimulus.asset_mapper.controllers_map_generator.admin');
        self::assertSame(ControllersMapGenerator::class, $admin->getClass());
        self::assertEquals(new Reference('asset_mapper'), $admin->getArgument(0));
        self::assertEquals(new Reference('stimulus.asset_mapper.ux_package_reader'), $admin->getArgument(1));
        self::assertSame([], $admin->getArgument(2));
        self::assertSame(__DIR__.'/../fixtures/assets/admin/controllers.json', $admin->getArgument(3));
        self::assertEquals(new Reference('stimulus.asset_mapper.auto_import_locator', ContainerInterface::NULL_ON_INVALID_REFERENCE), $admin->getArgument(4));
        self::assertSame(['/admin/controllers'], $admin->getArgument(5));
        self::assertNull($admin->getArgument(6));
        self::assertCount(7, $admin->getArguments());

        $front = $container->getDefinition('stimulus.asset_mapper.controllers_map_generator.front-office_2');
        self::assertSame(['/global/controllers'], $front->getArgument(2));
        self::assertSame('/global/controllers.json', $front->getArgument(3));
        self::assertSame(['/front/controllers'], $front->getArgument(5));

        self::assertEquals([
            'admin' => [
                'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
                'generator' => new Reference('stimulus.asset_mapper.controllers_map_generator.admin'),
            ],
            'front-office_2' => [
                'loader' => __DIR__.'/../fixtures/assets/front/stimulus_loader.js',
                'generator' => new Reference('stimulus.asset_mapper.controllers_map_generator.front-office_2'),
            ],
        ], $container->getDefinition('stimulus.asset_mapper.loader_javascript_compiler')->getArgument(2));

        self::assertSame(['/global/controllers'], $container->getDefinition('stimulus.asset_mapper.controllers_map_generator')->getArgument(2));
    }

    public function testLoadUsesGlobalDefaultsForApplications(): void
    {
        $container = $this->load([
            'applications' => [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
                ],
            ],
        ]);

        $admin = $container->getDefinition('stimulus.asset_mapper.controllers_map_generator.admin');
        self::assertSame(['%kernel.project_dir%/assets/controllers'], $admin->getArgument(2));
        self::assertSame('%kernel.project_dir%/assets/controllers.json', $admin->getArgument(3));
        self::assertSame([], $admin->getArgument(5));
        self::assertNull($admin->getArgument(6));
    }

    public function testLoadPassesTheGlobalControllersJsonWhenMerging(): void
    {
        $container = $this->load([
            'controllers_json' => '/global/controllers.json',
            'applications' => [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
                    'controllers_json' => '%stimulus_test.fixtures_dir%/assets/admin/controllers.json',
                    'merge_controllers_json' => true,
                ],
            ],
        ]);

        $admin = $container->getDefinition('stimulus.asset_mapper.controllers_map_generator.admin');
        self::assertSame(__DIR__.'/../fixtures/assets/admin/controllers.json', $admin->getArgument(3));
        self::assertSame('/global/controllers.json', $admin->getArgument(6));
    }

    /**
     * @param array<string, mixed> $applications
     */
    #[DataProvider('provideInvalidApplications')]
    public function testLoadThrowsOnInvalidApplications(array $applications, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->load(['applications' => $applications]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidApplications(): iterable
    {
        $adminLoader = __DIR__.'/../fixtures/assets/admin/stimulus_loader.js';
        $frontLoader = __DIR__.'/../fixtures/assets/front/stimulus_loader.js';

        yield 'uppercase name' => [['Admin' => ['loader' => $adminLoader]], 'Invalid Stimulus application name "Admin"'];
        yield 'name starting with a digit' => [['1x' => ['loader' => $adminLoader]], 'Invalid Stimulus application name "1x"'];
        yield 'name with a dot' => [['a.b' => ['loader' => $adminLoader]], 'Invalid Stimulus application name "a.b"'];
        yield 'reserved name' => [['default' => ['loader' => $adminLoader]], 'The Stimulus application name "default" is reserved.'];
        yield 'missing loader' => [['admin' => ['controller_paths' => []]], 'The child config "loader" under "stimulus.applications.admin" must be configured'];
        yield 'empty loader' => [['admin' => ['loader' => '']], 'The path "stimulus.applications.admin.loader" cannot contain an empty value'];
        yield 'empty controllers_json' => [['front' => ['loader' => $frontLoader, 'controllers_json' => '']], 'The "controllers_json" option cannot be an empty string.'];
        yield 'merge_controllers_json without controllers_json' => [['front' => ['loader' => $frontLoader, 'merge_controllers_json' => true]], 'The "merge_controllers_json" option requires the "controllers_json" option.'];
    }

    public function testLoadAcceptsTheDocumentedDefaults(): void
    {
        // same values as the documented YAML example, where "~" is null
        $container = $this->load(['applications' => ['admin' => [
            'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
            'controller_paths' => [],
            'include_global_paths' => true,
            'controllers_json' => null,
            'merge_controllers_json' => false,
        ]]]);

        $admin = $container->getDefinition('stimulus.asset_mapper.controllers_map_generator.admin');
        self::assertSame('%kernel.project_dir%/assets/controllers.json', $admin->getArgument(3));
        self::assertNull($admin->getArgument(6));
    }

    public function testLoadResolvesALoaderGivenAsAParameter(): void
    {
        $container = $this->load(['applications' => ['admin' => ['loader' => '%stimulus_test.admin_loader%']]]);

        self::assertSame(
            __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
            $container->getDefinition('stimulus.asset_mapper.loader_javascript_compiler')->getArgument(2)['admin']['loader'],
        );
    }

    public function testLoadThrowsOnLoaderNotEndingInJs(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('stimulus_test.loader', __DIR__.'/../fixtures/assets/controllers.json');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The loader "%s" of the "admin" Stimulus application must be a ".js" file.', __DIR__.'/../fixtures/assets/controllers.json'));

        new StimulusExtension()->load([['applications' => ['admin' => ['loader' => '%stimulus_test.loader%']]]], $container);
    }

    #[DataProvider('provideDuplicateLoaders')]
    public function testLoadThrowsOnDuplicateLoader(string $frontLoader): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The loader "%s" is used by both the "admin" and "front" Stimulus applications.', realpath(__DIR__.'/../fixtures/assets/admin/stimulus_loader.js')));

        $this->load(['applications' => [
            'admin' => ['loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js'],
            'front' => ['loader' => $frontLoader],
        ]]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideDuplicateLoaders(): iterable
    {
        yield 'same path' => [__DIR__.'/../fixtures/assets/admin/stimulus_loader.js'];
        yield 'equivalent path' => [__DIR__.'/../fixtures/assets/admin/../admin/stimulus_loader.js'];
        yield 'parameter' => ['%stimulus_test.admin_loader%'];
    }

    public function testLoadThrowsOnMissingLoaderFile(): void
    {
        $loader = __DIR__.'/../fixtures/assets/missing/stimulus_loader.js';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The loader file "%s" of the "admin" Stimulus application does not exist. Create it as an empty file inside an AssetMapper path.', $loader));

        $this->load(['applications' => ['admin' => ['loader' => $loader]]]);
    }

    public function testLoadThrowsOnMissingControllersJsonFile(): void
    {
        $controllersJson = __DIR__.'/../fixtures/assets/missing/controllers.json';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The controllers.json file "%s" of the "admin" Stimulus application does not exist.', $controllersJson));

        $this->load(['applications' => ['admin' => [
            'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
            'controllers_json' => $controllersJson,
        ]]]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('stimulus_test.fixtures_dir', __DIR__.'/../fixtures');
        $container->setParameter('stimulus_test.admin_loader', __DIR__.'/../fixtures/assets/admin/stimulus_loader.js');
        new StimulusExtension()->load([$config], $container);

        return $container;
    }

    /**
     * @param array<string, array{path: string}> $bundles
     *
     * @return array<array<string, mixed>>
     */
    private function prepend(array $bundles): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles_metadata', $bundles);
        new StimulusExtension()->prepend($container);

        return $container->getExtensionConfig('framework');
    }
}
