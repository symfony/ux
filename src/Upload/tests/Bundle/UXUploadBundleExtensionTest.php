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
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Upload\Command\CleanupCommand;
use Symfony\UX\Upload\Controller\UploadController;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\EventListener\FileValidationListener;
use Symfony\UX\Upload\Form\FileUploadType;
use Symfony\UX\Upload\Security\AnonymousUploadContextResolver;
use Symfony\UX\Upload\Security\SymfonySecurityUploadContextResolver;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Tests\NativeFunctions;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUpload;
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

    public function testLoadExtensionWithDefaultConfig()
    {
        $container = $this->bootKernel([]);
        $uploader = $container->get(UploaderInterface::class);

        self::assertInstanceOf(Uploader::class, $uploader);
        self::assertSame('default', $uploader->getName());
        self::assertSame(5 * 1024 * 1024, $uploader->getConfig()['chunk_size']);
        self::assertSame(Uploader::DEFAULT_MAX_SIZE, $uploader->getConfig()['max_size']);
        self::assertSame([], $uploader->getConfig()['allowed_types']);
    }

    public function testPrependsOptionalFrameworkAndTwigConfiguration()
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
        $expectedFrameworkConfig = [];
        if (interface_exists(\Symfony\Component\AssetMapper\AssetMapperInterface::class)) {
            $expectedFrameworkConfig['asset_mapper']['paths'][\dirname(__DIR__, 2).'/src/../assets/dist'] = '@symfony/ux-upload';
        }
        if (class_exists(\Symfony\Component\Translation\Translator::class)) {
            $expectedFrameworkConfig['translator']['paths'][] = \dirname(__DIR__, 2).'/src/../translations';
        }
        self::assertSame([] === $expectedFrameworkConfig ? [] : [$expectedFrameworkConfig], $builder->getExtensionConfig('framework'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testPrependsFrameworkConfigurationWhenOptionalComponentsAreInstalled()
    {
        if (!interface_exists(\Symfony\Component\AssetMapper\AssetMapperInterface::class)) {
            eval('namespace Symfony\Component\AssetMapper; interface AssetMapperInterface {}');
        }
        if (!class_exists(\Symfony\Component\Translation\Translator::class)) {
            eval('namespace Symfony\Component\Translation; class Translator {}');
        }

        $builder = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        new UXUploadBundle()->prependExtension(
            $this->createStub(ContainerConfigurator::class),
            $builder,
        );

        self::assertSame(
            [[
                'asset_mapper' => [
                    'paths' => [
                        \dirname(__DIR__, 2).'/src/../assets/dist' => '@symfony/ux-upload',
                    ],
                ],
                'translator' => [
                    'paths' => [
                        \dirname(__DIR__, 2).'/src/../translations',
                    ],
                ],
            ]],
            $builder->getExtensionConfig('framework'),
        );
    }

    public function testFileValidationListenerRunsAtItsDocumentedPriority()
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

    public function testSecurityContextResolverIsEnabledOnlyWhenSecurityBundleIsActive()
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

    public function testLoadExtensionWithCustomConfig()
    {
        $uploader = $this->bootKernel([
            'chunk_size' => '10M',
            'parallel_chunks' => 5,
            'compression' => false,
            'max_size' => '50M',
            'allowed_types' => ['image/jpeg', 'image/png'],
        ])->get(UploaderInterface::class);

        self::assertInstanceOf(Uploader::class, $uploader);
        self::assertSame(10 * 1024 * 1024, $uploader->getConfig()['chunk_size']);
        self::assertSame(50 * 1024 * 1024, $uploader->getConfig()['max_size']);
        self::assertSame(['image/jpeg', 'image/png'], $uploader->getConfig()['allowed_types']);
    }

    public function testCsrfTokenIsGeneratedAndValidatedThroughContainer()
    {
        $container = $this->bootKernel(['allow_anonymous' => true]);
        $requestStack = $container->get('test.request_stack');
        $session = new Session(new MockArraySessionStorage());
        $token = $this->csrfTokenFor($container, $requestStack, $session);
        $controller = $container->get(UploadController::class);

        $rejectedRequest = $this->initRequest('', $session);
        $requestStack->push($rejectedRequest);
        self::assertSame(403, $controller->init($rejectedRequest)->getStatusCode());
        $requestStack->pop();

        $acceptedRequest = $this->initRequest($token, $session);
        $requestStack->push($acceptedRequest);
        self::assertSame(200, $controller->init($acceptedRequest)->getStatusCode());
        $requestStack->pop();
    }

    public function testLocalStorageAndPrunableStorageUseTheSameService()
    {
        $container = $this->bootKernel([]);

        self::assertInstanceOf(LocalStorage::class, $container->get(StorageInterface::class));
        self::assertSame($container->get(StorageInterface::class), $container->get(PrunableStorageInterface::class));
    }

    public function testNamedUploaderIsAvailableFromTheLocator()
    {
        $container = $this->bootKernel([
            'uploaders' => [
                'documents' => [
                    'chunk_size' => '1M',
                    'max_size' => '20M',
                    'allowed_types' => ['application/pdf'],
                ],
            ],
        ]);

        $uploader = $container->get('test.uploaders')->get('documents');
        self::assertInstanceOf(UploaderInterface::class, $uploader);
        self::assertSame('documents', $uploader->getName());
        self::assertSame(1024 * 1024, $uploader->getConfig()['chunk_size']);
        self::assertSame(['application/pdf'], $uploader->getConfig()['allowed_types']);
    }

    public function testRejectsChunkSizeBelowMinimum()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"chunk_size" must be at least 64K');

        $this->bootKernel(['chunk_size' => '32K']);
    }

    public function testRejectsChunkSizeAboveMaximum()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"chunk_size" cannot exceed');

        $this->bootKernel(['chunk_size' => '65M']);
    }

    public function testRejectsNegativeMaximumSize()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"max_size" cannot be negative');

        $this->bootKernel(['max_size' => -1]);
    }

    public function testRejectsInvalidNamedUploaderChunkSize()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"uploaders.documents.chunk_size" must be at least 64K');

        $this->bootKernel(['uploaders' => ['documents' => ['chunk_size' => '32K']]]);
    }

    public function testRejectsNamedUploaderChunkSizeAboveMaximum()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"uploaders.documents.chunk_size" cannot exceed');

        $this->bootKernel(['uploaders' => ['documents' => ['chunk_size' => '65M']]]);
    }

    public function testRejectsInvalidNamedUploaderParallelism()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->bootKernel(['uploaders' => ['documents' => ['parallel_chunks' => 11]]]);
    }

    public function testRejectsInvalidNamedUploaderMaximumSize()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"uploaders.documents.max_size" cannot be negative');

        $this->bootKernel(['uploaders' => ['documents' => ['max_size' => -1]]]);
    }

    public function testNamedUploaderInheritsGlobalDefaults()
    {
        $container = $this->bootKernel([
            'chunk_size' => '8M',
            'max_size' => '200M',
            'allowed_types' => ['application/pdf'],
            'uploaders' => ['minimal' => []],
        ]);

        $uploader = $container->get('test.uploaders')->get('minimal');
        self::assertInstanceOf(UploaderInterface::class, $uploader);
        $namedConfig = $uploader->getConfig();
        self::assertSame(8 * 1024 * 1024, $namedConfig['chunk_size']);
        self::assertSame(200 * 1024 * 1024, $namedConfig['max_size']);
        self::assertSame(['application/pdf'], $namedConfig['allowed_types']);
    }

    public function testNamedUploaderCanExplicitlyAllowAllTypes()
    {
        $container = $this->bootKernel([
            'allowed_types' => ['application/pdf'],
            'uploaders' => ['unrestricted' => ['allowed_types' => []]],
        ]);

        $uploader = $container->get('test.uploaders')->get('unrestricted');
        self::assertInstanceOf(UploaderInterface::class, $uploader);
        self::assertSame([], $uploader->getConfig()['allowed_types']);
    }

    public function testNamedUploaderAcceptsAllSupportedOptions()
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

        $uploader = $container->get('test.uploaders')->get('media');
        self::assertInstanceOf(UploaderInterface::class, $uploader);
        $config = $uploader->getConfig();
        self::assertSame(1024 * 1024, $config['chunk_size']);
        self::assertSame(10 * 1024 * 1024, $config['max_size']);
        self::assertSame(['image/*'], $config['allowed_types']);

        $pending = $uploader->initializeUpload('image.jpg', 1024, 'image/jpeg');
        self::assertSame(2, $pending->parallel);
        self::assertFalse($pending->compression);
    }

    public function testIntegerMaximumSizeIsPreserved()
    {
        $config = $this->bootKernel(['max_size' => 10_000_000])
            ->get(UploaderInterface::class)
            ->getConfig();

        self::assertSame(10_000_000, $config['max_size']);
    }

    public function testFlysystemRequiresAnExplicitService()
    {
        if (!interface_exists(FilesystemOperator::class)) {
            self::markTestSkipped('league/flysystem is not installed.');
        }

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('no "flysystem_service" was configured');

        $this->bootKernel(['storage' => 'flysystem']);
    }

    public function testFlysystemConfigurationExplainsMissingOptionalDependency()
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

    public function testFlysystemUsesTheConfiguredService()
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

        $storage = new \ReflectionProperty(Uploader::class, 'storage')->getValue($container->get(UploaderInterface::class));
        self::assertInstanceOf(\Symfony\UX\Upload\Storage\FlysystemStorage::class, $storage);
    }

    public function testDistributedDeploymentRequiresSharedLock()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"ux_upload.shared_lock: true"');

        $this->bootKernel([
            'deployment' => 'distributed',
        ]);
    }

    public function testDistributedDeploymentRejectsLocalStorage()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('cannot use "storage: local"');

        $this->bootKernel([
            'deployment' => 'distributed',
            'shared_lock' => true,
        ]);
    }

    public function testRateLimiterIsDisabledByDefault()
    {
        $container = $this->bootKernel(['allow_anonymous' => true]);
        $requestStack = $container->get('test.request_stack');
        $session = new Session(new MockArraySessionStorage());
        $token = $this->csrfTokenFor($container, $requestStack, $session);
        $controller = $container->get(UploadController::class);

        for ($i = 0; $i < 3; ++$i) {
            $request = $this->initRequest($token, $session);
            $requestStack->push($request);
            self::assertSame(200, $controller->init($request)->getStatusCode());
            $requestStack->pop();
        }
    }

    public function testConfiguredFormTokenTtlIsApplied()
    {
        $container = $this->bootKernel(['form_token_ttl' => 120]);
        $now = new \DateTimeImmutable();
        $upload = new CompletedUpload(
            'upload-id',
            'default',
            '.tmp/completed/'.($now->getTimestamp() + 3600).'-upload-id.txt',
            'test.txt',
            'text/plain',
            4,
            $now,
            $now->modify('+1 hour'),
            access: new \Symfony\UX\Upload\Upload\CompletedUploadAccess($container->get(StorageInterface::class)),
        );

        $token = $container->get('test.upload_token_handler')->generate($upload);
        parse_str($token, $payload);

        self::assertArrayHasKey('x', $payload);
        self::assertGreaterThanOrEqual(time() + 118, (int) $payload['x']);
        self::assertLessThanOrEqual(time() + 120, (int) $payload['x']);
        self::assertSame($upload->expiresAt->getTimestamp(), (int) $payload['e']);
    }

    public function testConfiguredRateLimiterThrottlesInitialization()
    {
        if (!class_exists(\Symfony\Component\RateLimiter\RateLimiterFactory::class)) {
            self::markTestSkipped('symfony/rate-limiter is not installed.');
        }

        $container = $this->bootKernel(['rate_limiter' => 'limiter.ux_upload_init', 'allow_anonymous' => true]);
        $requestStack = $container->get('test.request_stack');
        $session = new Session(new MockArraySessionStorage());
        $token = $this->csrfTokenFor($container, $requestStack, $session);
        $controller = $container->get(UploadController::class);

        $first = $this->initRequest($token, $session, '203.0.113.7');
        $requestStack->push($first);
        self::assertSame(200, $controller->init($first)->getStatusCode());
        $requestStack->pop();

        $second = $this->initRequest($token, $session, '203.0.113.7');
        $requestStack->push($second);
        self::assertSame(429, $controller->init($second)->getStatusCode());
        $requestStack->pop();
    }

    public function testRemovedLocalStoragePublicPathIsRejected()
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->bootKernel([
            'local_storage' => [
                'directory' => '%kernel.project_dir%/var/tests/uploads/storage',
                'public_path' => '/uploads',
            ],
        ]);
    }

    public function testCleanupCommandIsRegisteredWhenConsoleIsInstalled()
    {
        if (!class_exists(\Symfony\Component\Console\Command\Command::class)) {
            self::markTestSkipped('symfony/console is not installed.');
        }

        self::assertInstanceOf(CleanupCommand::class, $this->bootKernel([])->get('test.cleanup_command'));
    }

    public function testBundlePathPointsToThePackageRoot()
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
        $token = $container->get('test.form_factory')->create(FileUploadType::class)->createView()->vars['stimulus_values']['csrfToken'];
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
