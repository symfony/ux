<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Bundle;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Symfony\UX\Upload\Command\CleanupCommand;
use Symfony\UX\Upload\Controller\UploadController;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\EventListener\FileValidationListener;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Security\AnonymousUploadContextResolver;
use Symfony\UX\Upload\Security\SymfonySecurityUploadContextResolver;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\FlysystemStorage;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Test\UploadedFileFactory;
use Symfony\UX\Upload\Tests\NativeFunctions;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\UploaderInterface;
use Symfony\UX\Upload\UXUploadBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

#[CoversClass(UXUploadBundle::class)]
final class UXUploadBundleExtensionTest extends TestCase
{
    private ?Kernel $kernel = null;
    private static int $kernelCounter = 0;

    protected function tearDown(): void
    {
        NativeFunctions::reset();
        $this->kernel?->shutdown();
        $this->kernel = null;
    }

    public function testLoadExtensionWithDefaultConfig(): void
    {
        $container = $this->bootKernel([]);
        $uploader = $container->get(UploaderInterface::class);

        self::assertInstanceOf(Uploader::class, $uploader);
        self::assertSame('default', $uploader->getName());
        self::assertSame(5 * 1024 * 1024, $uploader->getConfig()['chunk_size']);
        self::assertSame(Uploader::DEFAULT_MAX_SIZE, $uploader->getConfig()['max_size']);
        self::assertSame([], $uploader->getConfig()['allowed_types']);
    }

    public function testPrependsOptionalFrameworkAndTwigConfiguration(): void
    {
        $builder = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $builder->setParameter('kernel.bundles', ['TwigBundle' => TwigBundle::class]);

        new UXUploadBundle()->prependExtension(
            $this->createStub(ContainerConfigurator::class),
            $builder,
        );

        self::assertSame(
            [['form_themes' => ['@UXUpload/form_theme.html.twig']]],
            $builder->getExtensionConfig('twig'),
        );
        self::assertSame(
            [[
                'asset_mapper' => [
                    'paths' => [
                        Path::canonicalize(\dirname(__DIR__, 2).'/assets/dist') => '@symfony/ux-upload',
                    ],
                ],
                'translator' => [
                    'paths' => [
                        Path::canonicalize(\dirname(__DIR__, 2).'/translations'),
                    ],
                ],
            ]],
            $builder->getExtensionConfig('framework'),
        );
    }

    public function testFileValidationListenerRunsAtItsDocumentedPriority(): void
    {
        $dispatcher = $this->bootKernel([])->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        foreach ($dispatcher->getListeners(UploadAssembledEvent::class) as $listener) {
            $listenerService = \is_array($listener) ? $listener[0] : $listener;
            if ($listenerService instanceof FileValidationListener) {
                self::assertSame(
                    FileValidationListener::PRIORITY,
                    $dispatcher->getListenerPriority(UploadAssembledEvent::class, $listener),
                );

                return;
            }
        }

        self::fail('The file validation listener is not registered for UploadAssembledEvent.');
    }

    public function testSecurityContextResolverIsEnabledOnlyWhenSecurityBundleIsActive(): void
    {
        self::assertInstanceOf(
            AnonymousUploadContextResolver::class,
            $this->bootKernel([])->get(UploadContextResolverInterface::class),
        );

        if (!class_exists(SecurityBundle::class)) {
            return;
        }

        self::assertInstanceOf(
            SymfonySecurityUploadContextResolver::class,
            $this->bootKernel([], securityBundle: true)->get(UploadContextResolverInterface::class),
        );
    }

    public function testLoadExtensionWithCustomConfig(): void
    {
        $uploader = $this->bootKernel([
            'chunk_size' => '10M',
            'parallel_chunks' => 5,
            'compression' => false,
            'max_size' => 10_000_000,
            'allowed_types' => ['image/jpeg', 'image/png'],
        ])->get(UploaderInterface::class);

        self::assertInstanceOf(Uploader::class, $uploader);
        self::assertSame(10 * 1024 * 1024, $uploader->getConfig()['chunk_size']);
        self::assertSame(10_000_000, $uploader->getConfig()['max_size']);
        self::assertSame(['image/jpeg', 'image/png'], $uploader->getConfig()['allowed_types']);
    }

    public function testCsrfTokenIsGeneratedAndValidatedThroughContainer(): void
    {
        $container = $this->bootKernel(['allow_anonymous' => true]);
        $requestStack = self::service($container, 'test.request_stack', RequestStack::class);
        $session = new Session(new MockArraySessionStorage());
        $token = $this->csrfTokenFor($container, $requestStack, $session);
        $controller = self::service($container, UploadController::class, UploadController::class);

        $rejectedRequest = $this->initRequest('', $session);
        $requestStack->push($rejectedRequest);
        self::assertSame(403, $controller->init($rejectedRequest)->getStatusCode());
        $requestStack->pop();

        $acceptedRequest = $this->initRequest($token, $session);
        $requestStack->push($acceptedRequest);
        self::assertSame(200, $controller->init($acceptedRequest)->getStatusCode());
        $requestStack->pop();
    }

    public function testLocalStorageAndPrunableStorageUseTheSameService(): void
    {
        $container = $this->bootKernel([]);

        self::assertInstanceOf(LocalStorage::class, $container->get(StorageInterface::class));
        self::assertSame($container->get(StorageInterface::class), $container->get(PrunableStorageInterface::class));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, ?string}>
     */
    public static function invalidConfigurations(): iterable
    {
        yield 'chunk size below the minimum' => [['chunk_size' => '32K'], '"chunk_size" must be at least 64K'];
        yield 'chunk size above the maximum' => [['chunk_size' => '65M'], '"chunk_size" cannot exceed'];
        yield 'negative maximum size' => [['max_size' => -1], '"max_size" cannot be negative'];
        yield 'named uploader chunk size below the minimum' => [['uploaders' => ['documents' => ['chunk_size' => '32K']]], '"uploaders.documents.chunk_size" must be at least 64K'];
        yield 'named uploader chunk size above the maximum' => [['uploaders' => ['documents' => ['chunk_size' => '65M']]], '"uploaders.documents.chunk_size" cannot exceed'];
        yield 'named uploader parallelism above the maximum' => [['uploaders' => ['documents' => ['parallel_chunks' => 11]]], null];
        yield 'named uploader negative maximum size' => [['uploaders' => ['documents' => ['max_size' => -1]]], '"uploaders.documents.max_size" cannot be negative'];
        yield 'distributed deployment without a shared lock' => [['deployment' => 'distributed'], '"ux_upload.shared_lock: true"'];
        yield 'distributed deployment with local storage' => [['deployment' => 'distributed', 'shared_lock' => true], 'cannot use "storage: local"'];
        yield 'removed local storage public path' => [['local_storage' => [
            'directory' => '%kernel.project_dir%/var/tests/uploads/storage',
            'public_path' => '/uploads',
        ]], null];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('invalidConfigurations')]
    public function testRejectsInvalidConfiguration(array $config, ?string $message): void
    {
        $this->expectException(InvalidConfigurationException::class);
        if (null !== $message) {
            $this->expectExceptionMessage($message);
        }

        $this->bootKernel($config);
    }

    public function testNamedUploaderInheritsGlobalDefaults(): void
    {
        $container = $this->bootKernel([
            'chunk_size' => '8M',
            'max_size' => '200M',
            'allowed_types' => ['application/pdf'],
            'uploaders' => ['minimal' => []],
        ]);

        $uploader = self::service($container, 'test.uploaders', ServiceProviderInterface::class)->get('minimal');
        self::assertInstanceOf(UploaderInterface::class, $uploader);
        $namedConfig = $uploader->getConfig();
        self::assertSame(8 * 1024 * 1024, $namedConfig['chunk_size']);
        self::assertSame(200 * 1024 * 1024, $namedConfig['max_size']);
        self::assertSame(['application/pdf'], $namedConfig['allowed_types']);
    }

    public function testNamedUploaderCanExplicitlyAllowAllTypes(): void
    {
        $container = $this->bootKernel([
            'allowed_types' => ['application/pdf'],
            'uploaders' => ['unrestricted' => ['allowed_types' => []]],
        ]);

        $uploader = self::service($container, 'test.uploaders', ServiceProviderInterface::class)->get('unrestricted');
        self::assertInstanceOf(UploaderInterface::class, $uploader);
        self::assertSame([], $uploader->getConfig()['allowed_types']);
    }

    public function testNamedUploaderAcceptsAllSupportedOptions(): void
    {
        $container = $this->bootKernel([
            'uploaders' => [
                'media' => [
                    'chunk_size' => '1M',
                    'max_size' => '10M',
                    'allowed_types' => ['image/*'],
                    'parallel_chunks' => 2,
                    'compression' => false,
                ],
            ],
        ]);

        $uploader = self::service($container, 'test.uploaders', ServiceProviderInterface::class)->get('media');
        self::assertInstanceOf(UploaderInterface::class, $uploader);
        self::assertSame('media', $uploader->getName());
        $config = $uploader->getConfig();
        self::assertSame(1024 * 1024, $config['chunk_size']);
        self::assertSame(10 * 1024 * 1024, $config['max_size']);
        self::assertSame(['image/*'], $config['allowed_types']);

        $pending = $uploader->initializeUpload('image.jpg', 1024, 'image/jpeg');
        self::assertSame(2, $pending->parallel);
        self::assertFalse($pending->compression);
    }

    public function testFlysystemRequiresAnExplicitService(): void
    {
        if (!interface_exists(FilesystemOperator::class)) {
            self::markTestSkipped('league/flysystem is not installed.');
        }

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('no "flysystem_service" was configured');

        $this->bootKernel(['storage' => 'flysystem']);
    }

    public function testFlysystemConfigurationExplainsMissingOptionalDependency(): void
    {
        NativeFunctions::mock(
            'Symfony\\UX\\Upload\\interface_exists',
            NativeFunctions::PASSTHROUGH,
            false,
        );

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('league/flysystem is not installed');

        $this->bootKernel([
            'storage' => 'flysystem',
            'flysystem_service' => 'test.flysystem',
        ]);
    }

    public function testFlysystemUsesTheConfiguredService(): void
    {
        if (!interface_exists(FilesystemOperator::class)) {
            self::markTestSkipped('league/flysystem is not installed.');
        }

        $directory = sys_get_temp_dir().'/ux_upload_bundle_fs_'.bin2hex(random_bytes(6));
        $container = $this->bootKernel(
            ['storage' => 'flysystem', 'flysystem_service' => 'test.flysystem'],
            static function (ContainerConfigurator $container) use ($directory): void {
                $container->services()
                    ->set('test.flysystem.adapter', LocalFilesystemAdapter::class)
                    ->args([$directory]);
                $container->services()
                    ->set('test.flysystem', Filesystem::class)
                    ->args([service('test.flysystem.adapter')])
                    ->public();
            },
        );

        self::assertInstanceOf(FlysystemStorage::class, $container->get(StorageInterface::class));
        self::assertSame($container->get(StorageInterface::class), $container->get(PrunableStorageInterface::class));
    }

    public function testConfiguredFormTokenTtlIsApplied(): void
    {
        $container = $this->bootKernel(['form_token_ttl' => 120]);
        $upload = new UploadedFileFactory()->create(self::service($container, StorageInterface::class, StorageInterface::class));

        $token = self::service($container, 'test.upload_token_handler', UploadTokenHandler::class)->generate($upload);
        parse_str($token, $payload);

        self::assertArrayHasKey('x', $payload);
        self::assertGreaterThanOrEqual(time() + 118, (int) $payload['x']);
        self::assertLessThanOrEqual(time() + 120, (int) $payload['x']);
        self::assertSame($upload->getExpiresAt()->getTimestamp(), (int) $payload['e']);
    }

    public function testConfiguredRateLimiterThrottlesInitialization(): void
    {
        if (!class_exists(\Symfony\Component\RateLimiter\RateLimiterFactory::class)) {
            self::markTestSkipped('symfony/rate-limiter is not installed.');
        }

        $container = $this->bootKernel(['rate_limiter' => 'limiter.ux_upload_init', 'allow_anonymous' => true]);
        $requestStack = self::service($container, 'test.request_stack', RequestStack::class);
        $session = new Session(new MockArraySessionStorage());
        $token = $this->csrfTokenFor($container, $requestStack, $session);
        $controller = self::service($container, UploadController::class, UploadController::class);

        $first = $this->initRequest($token, $session, '203.0.113.7');
        $requestStack->push($first);
        self::assertSame(200, $controller->init($first)->getStatusCode());
        $requestStack->pop();

        $second = $this->initRequest($token, $session, '203.0.113.7');
        $requestStack->push($second);
        self::assertSame(429, $controller->init($second)->getStatusCode());
        $requestStack->pop();
    }

    public function testCleanupCommandIsRegisteredWhenConsoleIsInstalled(): void
    {
        if (!class_exists(\Symfony\Component\Console\Command\Command::class)) {
            self::markTestSkipped('symfony/console is not installed.');
        }

        self::assertInstanceOf(CleanupCommand::class, $this->bootKernel([])->get('test.cleanup_command'));
    }

    public function testBundlePathPointsToThePackageRoot(): void
    {
        self::assertSame(\dirname(__DIR__, 2), new UXUploadBundle()->getPath());
    }

    private function csrfTokenFor(
        ContainerInterface $container,
        RequestStack $requestStack,
        Session $session,
    ): string {
        $formRequest = Request::create('/form');
        $formRequest->setSession($session);
        $requestStack->push($formRequest);
        $token = self::service($container, 'test.form_factory', FormFactoryInterface::class)->create(UploadType::class)->createView()->vars['stimulus_values']['csrfToken'];
        $requestStack->pop();

        self::assertIsString($token);
        self::assertNotSame('', $token);

        return $token;
    }

    private function initRequest(string $token, Session $session, ?string $ip = null): Request
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $token,
        ];
        if (null !== $ip) {
            $server['REMOTE_ADDR'] = $ip;
        }

        $request = Request::create('/upload/init', 'POST', server: $server, content: json_encode([
            'filename' => 'test.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
        ], \JSON_THROW_ON_ERROR));
        $request->setSession($session);

        return $request;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private static function service(ContainerInterface $container, string $id, string $class): object
    {
        $service = $container->get($id);
        self::assertInstanceOf($class, $service);

        return $service;
    }

    /**
     * @param array<string, mixed>                       $uploadConfig
     * @param \Closure(ContainerConfigurator): void|null $configureServices
     */
    private function bootKernel(array $uploadConfig, ?\Closure $configureServices = null, bool $securityBundle = false): ContainerInterface
    {
        $this->kernel?->shutdown();
        $this->kernel = null;

        $counter = ++self::$kernelCounter;
        $this->kernel = new class($counter, $uploadConfig, $configureServices, $securityBundle, 'test', false) extends Kernel {
            use MicroKernelTrait;

            /**
             * @param array<string, mixed>                       $uploadConfig
             * @param \Closure(ContainerConfigurator): void|null $configureServices
             */
            public function __construct(
                private readonly int $counter,
                private readonly array $uploadConfig,
                private readonly ?\Closure $configureServices,
                private readonly bool $securityBundle,
                string $environment,
                bool $debug,
            ) {
                parent::__construct($environment, $debug);
            }

            public function registerBundles(): iterable
            {
                yield new FrameworkBundle();
                yield new TwigBundle();
                if ($this->securityBundle) {
                    yield new SecurityBundle();
                }
                yield new UXUploadBundle();
            }

            protected function configureContainer(ContainerConfigurator $container): void
            {
                $frameworkConfig = [
                    'secret' => 'S0CRET',
                    'http_method_override' => false,
                    'test' => true,
                    'lock' => 'flock',
                    'csrf_protection' => true,
                    'session' => [
                        'storage_factory_id' => 'session.storage.factory.mock_file',
                    ],
                ];
                if (isset($this->uploadConfig['rate_limiter'])) {
                    $frameworkConfig['rate_limiter'] = [
                        'ux_upload_init' => [
                            'policy' => 'sliding_window',
                            'limit' => 1,
                            'interval' => '1 minute',
                        ],
                    ];
                }
                $container->extension('framework', $frameworkConfig);
                if ($this->securityBundle) {
                    $container->extension('security', [
                        'providers' => [
                            'users' => ['memory' => []],
                        ],
                        'firewalls' => [
                            'main' => ['security' => false],
                        ],
                    ]);
                }
                $container->extension('ux_upload', array_merge([
                    'storage' => 'local',
                    'temp_dir' => '%kernel.project_dir%/var/tests/uploads/tmp',
                    'local_storage' => [
                        'directory' => '%kernel.project_dir%/var/tests/uploads/storage',
                    ],
                ], $this->uploadConfig));

                if (null !== $this->configureServices) {
                    ($this->configureServices)($container);
                }

                $container->services()
                    ->alias('test.form_factory', FormFactoryInterface::class)
                    ->public();
                $container->services()
                    ->alias('test.request_stack', RequestStack::class)
                    ->public();
                $container->services()
                    ->alias('test.uploaders', 'ux_upload.uploaders')
                    ->public();
                $container->services()
                    ->alias('test.upload_token_handler', UploadTokenHandler::class)
                    ->public();
                if (class_exists(\Symfony\Component\Console\Command\Command::class)) {
                    $container->services()
                        ->alias('test.cleanup_command', 'ux_upload.command.cleanup')
                        ->public();
                }
            }

            protected function configureRoutes(RoutingConfigurator $routes): void
            {
                $routes->import(\dirname(__DIR__, 2).'/config/routes.php');
            }

            public function getCacheDir(): string
            {
                return sys_get_temp_dir().'/ux_upload_ext_test_'.getmypid().'_'.$this->counter;
            }

            public function getLogDir(): string
            {
                return sys_get_temp_dir().'/ux_upload_ext_log_'.getmypid().'_'.$this->counter;
            }
        };

        $this->kernel->boot();

        return $this->kernel->getContainer();
    }
}
