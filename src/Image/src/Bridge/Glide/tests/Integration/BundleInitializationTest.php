<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Image\Bridge\Glide\Controller\GlideController;
use Symfony\UX\Image\Bridge\Glide\GlideProvider;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\UXImageBundle;

/**
 * Only testable from this package, where "symfony/ux-image" and "league/glide" are both actually
 * Composer-installed side by side; a bare, uncompiled ContainerBuilder won't resolve "%env(...)%".
 */
final class BundleInitializationTest extends TestCase
{
    private string $source;
    private string $cache;

    protected function setUp(): void
    {
        $this->source = sys_get_temp_dir().'/ux_image_glide_bundle_init_'.bin2hex(random_bytes(8));
        $this->cache = $this->source.'/cache';
        mkdir($this->source, recursive: true);
        mkdir($this->cache, recursive: true);

        $image = imagecreatetruecolor(10, 10);
        imagefill($image, 0, 0, imagecolorallocate($image, 0, 0, 255));
        imagejpeg($image, $this->source.'/hero.jpg', 90);
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->source);

        putenv('UX_IMAGE_DSN');
        unset($_ENV['UX_IMAGE_DSN'], $_SERVER['UX_IMAGE_DSN']);
    }

    public function testGlideControllerResolvesAndServesARequestWhenTheDsnIsAnUnresolvedEnvPlaceholder()
    {
        $this->setDsnEnvVar($this->dsn());

        $controller = $this->compileAndGetGlideController(['provider' => '%env(UX_IMAGE_DSN)%']);

        $response = $controller(Request::create('/images/hero.jpg?w=5'), 'hero.jpg');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testASignKeyCarriedByTheEnvPlaceholderDsnIsValidatedServerSide()
    {
        $signKey = 's3cret';
        $this->setDsnEnvVar(\sprintf('glide://default/images?source=%s&cache=%s&sign_key=%s', $this->source, $this->cache, $signKey));

        $controller = $this->compileAndGetGlideController(['provider' => '%env(UX_IMAGE_DSN)%']);

        $unsigned = $controller(Request::create('/images/hero.jpg?w=5'), 'hero.jpg');
        self::assertSame(403, $unsigned->getStatusCode());

        $signedUrl = new GlideProvider('/images', $signKey)->generateUrl(new ImageTransformation('hero.jpg', width: 5));
        $signed = $controller(Request::create($signedUrl), 'hero.jpg');
        self::assertSame(200, $signed->getStatusCode());
    }

    public function testAContainerParameterInsideTheDsnReachesGlideExpanded()
    {
        // Needs "%env(resolve:UX_IMAGE_DSN)%", not "%env(UX_IMAGE_DSN)%": parameter resolution doesn't recurse into env values.
        $this->setDsnEnvVar('glide://default/images?source=%kernel.project_dir%&cache=%kernel.project_dir%/cache');

        $controller = $this->compileAndGetGlideController(['provider' => '%env(resolve:UX_IMAGE_DSN)%']);

        $response = $controller(Request::create('/images/hero.jpg?w=5'), 'hero.jpg');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testTheConfiguredFormatsBecomeTheControllerNegotiationCandidates()
    {
        $this->setDsnEnvVar($this->dsn());

        $request = Request::create('/images/hero.jpg?w=5&fm=auto');
        $request->headers->set('Accept', 'image/webp,*/*');

        $wide = $this->compileAndGetGlideController(['provider' => '%env(UX_IMAGE_DSN)%']);
        self::assertSame('image/webp', $wide($request, 'hero.jpg')->headers->get('Content-Type'));
    }

    public function testAConfiguredJpegCandidateIsActuallyNegotiatedNotJustFallenBackTo()
    {
        $this->setDsnEnvVar($this->dsn());

        $request = Request::create('/images/hero.jpg?w=5&fm=auto');
        $request->headers->set('Accept', 'image/jpeg,image/avif,*/*');

        $controller = $this->compileAndGetGlideController(['provider' => '%env(UX_IMAGE_DSN)%', 'formats' => ['jpeg', 'avif']]);

        self::assertSame('image/jpeg', $controller($request, 'hero.jpg')->headers->get('Content-Type'));
    }

    private function dsn(): string
    {
        return \sprintf('glide://default/images?source=%s&cache=%s', $this->source, $this->cache);
    }

    private function setDsnEnvVar(string $dsn): void
    {
        putenv('UX_IMAGE_DSN='.$dsn);
        $_ENV['UX_IMAGE_DSN'] = $dsn;
        $_SERVER['UX_IMAGE_DSN'] = $dsn;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function compileAndGetGlideController(array $config): GlideController
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->source);

        new UXImageBundle()->getContainerExtension()->load([$config], $container);

        $container->getDefinition(GlideController::class)->setPublic(true);

        $container->compile();

        $dumper = new PhpDumper($container);
        $class = 'GlideBundleInitializationTestContainer'.bin2hex(random_bytes(8));
        eval(preg_replace('/^<\?php\s*/', '', $dumper->dump(['class' => $class])));

        $compiled = new $class();

        return $compiled->get(GlideController::class);
    }
}
