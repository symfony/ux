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
 * It is NOT a PHPUnit test. It boots UXImageBundle inside a fresh PHP process
 * where a chosen set of optional packages (league/flysystem, intervention/image,
 * doctrine/dbal, symfony/ux-twig-component, ...) has been made invisible to the
 * autoloader, then reports the outcome as a single JSON line on stdout.
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
 *     "ux_image": { ... },                       // ux_image extension config
 *     "action": "boot" | "process",              // what to do once booted
 *     "has": ["ux_image.twig.component"]          // service ids to probe
 *   }
 * Output: JSON object on stdout
 *   { "status": "ok",    "has": {"id": bool}, "process": {...}|null }
 *   { "status": "error", "class": "...", "message": "..." }
 */

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Image\Processor\ImageProcessorInterface;
use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\UXImageBundle;

require dirname(__DIR__, 2).'/vendor/autoload.php';

/**
 * @param array<string, mixed> $payload
 */
function emit(array $payload): void
{
    echo json_encode($payload, \JSON_THROW_ON_ERROR), "\n";
    exit(0);
}

try {
    $raw = stream_get_contents(\STDIN);
    /** @var array{hidden?: list<string>, ux_image?: array<string, mixed>, action?: string, has?: list<string>, twig_component_enabled?: bool} $input */
    $input = json_decode((string) $raw, true, 512, \JSON_THROW_ON_ERROR);

    $hidden = $input['hidden'] ?? [];
    foreach (Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
        foreach ($hidden as $prefix) {
            // Emptying the PSR-4 dirs makes findFile() miss for this namespace,
            // so class_exists()/interface_exists() report the package as absent.
            $loader->setPsr4($prefix, []);
        }
    }

    $uxImageConfig = $input['ux_image'] ?? [];
    $action = $input['action'] ?? 'boot';
    $twigComponentEnabled = $input['twig_component_enabled'] ?? true;

    $kernel = new class(uniqid('', true), $uxImageConfig, $twigComponentEnabled) extends Kernel {
        use MicroKernelTrait;

        /**
         * @param array<string, mixed> $imageConfig
         */
        public function __construct(
            private readonly string $id,
            private readonly array $imageConfig,
            private readonly bool $twigComponentEnabled,
        ) {
            parent::__construct('test', false);
        }

        public function registerBundles(): iterable
        {
            yield new FrameworkBundle();
            // Companion bundles are registered only when installed, exactly like a
            // real app: hiding a namespace drops both the classes AND its bundle,
            // so the "package absent" and "package present" branches are both real.
            if (class_exists(Symfony\Bundle\TwigBundle\TwigBundle::class)) {
                yield new Symfony\Bundle\TwigBundle\TwigBundle();
            }
            if ($this->twigComponentEnabled && class_exists(Symfony\UX\TwigComponent\TwigComponentBundle::class)) {
                yield new Symfony\UX\TwigComponent\TwigComponentBundle();
            }
            yield new UXImageBundle();
        }

        protected function configureContainer(ContainerConfigurator $container): void
        {
            $container->extension('framework', [
                'secret' => 'S0CRET',
                'http_method_override' => false,
                'test' => true,
            ]);

            if ($this->twigComponentEnabled && class_exists(Symfony\UX\TwigComponent\TwigComponentBundle::class)) {
                $container->extension('twig_component', [
                    'defaults' => [],
                    'anonymous_template_directory' => 'components',
                ]);
            }

            $container->extension('ux_image', $this->imageConfig);

            $services = $container->services()
                ->alias('test.processor', ImageProcessorInterface::class)->public()
                ->alias('test.renderer', ImageRendererInterface::class)->public();

            if ($this->twigComponentEnabled && class_exists(Symfony\UX\TwigComponent\TwigComponentBundle::class)) {
                $services->alias('test.component_factory', 'ux.twig_component.component_factory')->public();
            }
        }

        protected function configureRoutes(RoutingConfigurator $routes): void
        {
        }

        public function getProjectDir(): string
        {
            return sys_get_temp_dir().'/ux_image_isolated_'.$this->id;
        }

        public function getCacheDir(): string
        {
            return $this->getProjectDir().'/cache';
        }

        public function getLogDir(): string
        {
            return $this->getProjectDir().'/log';
        }
    };

    $kernel->boot();
    $container = $kernel->getContainer();

    $process = null;
    if ('process' === $action) {
        // A real GD-encoded PNG exercises the native gd processor end to end:
        // store (LocalStorage) -> resize/convert (GdImageProcessor) -> render.
        $source = tempnam(sys_get_temp_dir(), 'ux_img_iso_').'.png';
        $gd = imagecreatetruecolor(64, 48);
        imagepng($gd, $source);
        imagedestroy($gd);

        $file = new class($source, 'sample.png', 'image/png', null, true) extends UploadedFile {
            public function getMimeType(): string
            {
                return $this->getClientMimeType();
            }
        };

        $processor = $container->get('test.processor');
        assert($processor instanceof ImageProcessorInterface);
        $asset = $processor->process($file, 'isolation_test');

        $renderer = $container->get('test.renderer');
        assert($renderer instanceof ImageRendererInterface);
        $rendered = $renderer->render($asset);

        $process = [
            'variant_formats' => array_keys($asset->variants ?? []),
            'fallback_src' => $rendered->fallbackSrc,
            'source_count' => count($rendered->sources),
        ];
    }

    $has = [];
    foreach ($input['has'] ?? [] as $id) {
        $has[$id] = $container->has($id);
    }

    // Whether the <twig:ux:image> component is known to TwigComponent's factory:
    // true/false when ux-twig-component is installed, null when it is absent.
    $componentRegistered = null;
    if ($container->has('test.component_factory')) {
        $factory = $container->get('test.component_factory');
        if ($factory instanceof Symfony\UX\TwigComponent\ComponentFactory) {
            try {
                $factory->metadataFor('ux:image');
                $componentRegistered = true;
            } catch (Throwable) {
                $componentRegistered = false;
            }
        }
    }

    $kernel->shutdown();

    emit(['status' => 'ok', 'has' => $has, 'process' => $process, 'component_registered' => $componentRegistered]);
} catch (Throwable $e) {
    emit(['status' => 'error', 'class' => $e::class, 'message' => $e->getMessage()]);
}
