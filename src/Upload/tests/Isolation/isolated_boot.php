<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * Child process entrypoint for MissingOptionalDependencyTest.
 *
 * It is NOT a PHPUnit test. It boots UXUploadBundle inside a fresh PHP process
 * where a chosen set of optional packages (league/flysystem, symfony/console,
 * symfony/security-csrf, ...) has been made invisible to the autoloader, then
 * reports the outcome as a single JSON line on stdout.
 *
 * Why a separate process instead of an in-process test:
 * the optional packages live in this bundle's own require-dev (they are needed
 * to run the rest of the suite), so their classes are always autoloadable in
 * the test runner. class_exists()/interface_exists() therefore always return
 * true in-process and the "package is absent" branch can never be exercised.
 * A child process lets us neutralise the relevant PSR-4 prefixes on Composer's
 * ClassLoader before anything loads them, which is exactly what "the package is
 * not installed" looks like to the container-compile guards.
 *
 * Input : JSON object on stdin
 *   {
 *     "hidden": ["League\\Flysystem\\", ...],   // PSR-4 prefixes to hide
 *     "csrf_protection": false,                  // framework csrf_protection.enabled
 *     "ux_upload": { ... },                      // ux_upload extension config
 *     "action": "boot" | "create_form" | "run_upload", // what to do once booted
 *     "has": ["ux_upload.command.cleanup"]       // service ids to probe
 *   }
 * Output: JSON object on stdout
 *   { "status": "ok",    "csrf_token": "..."|null, "has": {"id": bool}, "upload": {...}|null }
 *   { "status": "error", "class": "...", "message": "..." }
 */

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Upload\Form\FileUploadType;
use Symfony\UX\Upload\UploaderInterface;
use Symfony\UX\Upload\UXUploadBundle;

require dirname(__DIR__, 2).'/vendor/autoload.php';

/**
 * @param array<string, mixed> $payload
 *
 * @return never
 */
function emit(array $payload): void
{
    echo json_encode($payload, \JSON_THROW_ON_ERROR), "\n";
    exit(0);
}

try {
    $raw = stream_get_contents(\STDIN);
    /** @var array{hidden?: list<string>, csrf_protection?: bool, ux_upload?: array<string, mixed>, action?: string, has?: list<string>} $input */
    $input = json_decode((string) $raw, true, 512, \JSON_THROW_ON_ERROR);

    $hidden = $input['hidden'] ?? [];
    foreach (Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
        foreach ($hidden as $prefix) {
            // Emptying the PSR-4 dirs makes findFile() miss for this namespace,
            // so class_exists()/interface_exists() report the package as absent.
            $loader->setPsr4($prefix, []);
        }
    }

    $csrfProtection = $input['csrf_protection'] ?? false;
    $uxUploadConfig = $input['ux_upload'] ?? [];
    $action = $input['action'] ?? 'boot';

    $kernel = new class(uniqid('', true), $csrfProtection, $uxUploadConfig) extends Kernel {
        use MicroKernelTrait;

        /**
         * @param array<string, mixed> $uploadConfig
         */
        public function __construct(
            private readonly string $id,
            private readonly bool $csrfProtection,
            private readonly array $uploadConfig,
        ) {
            parent::__construct('test', false);
        }

        public function registerBundles(): iterable
        {
            yield new FrameworkBundle();
            yield new UXUploadBundle();
        }

        protected function configureContainer(ContainerConfigurator $container): void
        {
            $frameworkConfig = [
                'secret' => 'S0CRET',
                'http_method_override' => false,
                'test' => true,
                'csrf_protection' => $this->csrfProtection,
                'session' => [
                    'storage_factory_id' => 'session.storage.factory.mock_file',
                ],
            ];
            if (class_exists(Symfony\Component\Lock\LockFactory::class)) {
                // SemaphoreStore is unavailable in some sandboxed macOS test
                // environments and retries indefinitely. Flock exercises the
                // real lock integration without relying on SysV semaphores.
                $frameworkConfig['lock'] = 'flock';
            }
            $container->extension('framework', $frameworkConfig);

            $config = array_merge([
                'storage' => 'local',
                'temp_dir' => '%kernel.project_dir%/var/tests/uploads/tmp',
                'local_storage' => [
                    'directory' => '%kernel.project_dir%/var/tests/uploads/storage',
                ],
            ], $this->uploadConfig);

            $container->extension('ux_upload', $config);

            $container->services()
                ->alias('test.form_factory', FormFactoryInterface::class)->public()
                ->alias('test.request_stack', RequestStack::class)->public()
                ->alias('test.uploader', UploaderInterface::class)->public();
        }

        protected function configureRoutes(RoutingConfigurator $routes): void
        {
            $routes->import(dirname(__DIR__, 2).'/config/routes.php');
        }

        public function getCacheDir(): string
        {
            return sys_get_temp_dir().'/ux_upload_isolated_'.$this->id.'/cache';
        }

        public function getLogDir(): string
        {
            return sys_get_temp_dir().'/ux_upload_isolated_'.$this->id.'/log';
        }
    };

    $kernel->boot();
    $container = $kernel->getContainer();

    $csrfToken = null;
    if ('create_form' === $action) {
        $requestStack = $container->get('test.request_stack');
        assert($requestStack instanceof RequestStack);
        $request = Request::create('/form');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        $formFactory = $container->get('test.form_factory');
        assert($formFactory instanceof FormFactoryInterface);
        $form = $formFactory->create(FileUploadType::class);
        $csrfToken = $form->createView()->vars['stimulus_values']['csrfToken'] ?? null;
    }

    $upload = null;
    if ('run_upload' === $action) {
        $uploader = $container->get('test.uploader');
        assert($uploader instanceof UploaderInterface);

        $content = 'hello world';
        $pending = $uploader->initializeUpload('note.txt', strlen($content), 'text/plain');
        $uploader->storeChunk($pending->uploadId, 0, $content);
        $completed = $uploader->completeUpload($pending->uploadId);

        $upload = [
            'uploadId' => $pending->uploadId,
            'filename' => $completed->originalName,
            'size' => $completed->size,
        ];
    }

    $has = [];
    foreach ($input['has'] ?? [] as $id) {
        $has[$id] = $container->has($id);
    }

    $kernel->shutdown();

    emit(['status' => 'ok', 'csrf_token' => $csrfToken, 'has' => $has, 'upload' => $upload]);
} catch (Throwable $e) {
    emit(['status' => 'error', 'class' => $e::class, 'message' => $e->getMessage()]);
}
