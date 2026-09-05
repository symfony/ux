<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide\Tests\Controller;

use League\Glide\ServerFactory;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\Image\Bridge\Glide\Controller\GlideController;
use Symfony\UX\Image\Bridge\Glide\GlideProvider;
use Symfony\UX\Image\Bridge\Glide\SymfonyResponseFactory;
use Symfony\UX\Image\ImageTransformation;

final class GlideControllerTest extends TestCase
{
    private const string SIGN_KEY = 's3cret';

    private string $source;
    private string $cache;

    protected function setUp(): void
    {
        $this->source = sys_get_temp_dir().'/ux_image_glide_test_'.bin2hex(random_bytes(8));
        $this->cache = $this->source.'/cache';
        mkdir($this->source, recursive: true);
        mkdir($this->cache, recursive: true);

        $image = imagecreatetruecolor(20, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));
        imagejpeg($image, $this->source.'/hero.jpg', 90);
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->source);
    }

    public function testTheControllerSetsVaryAccept()
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', 'image/avif,image/webp,*/*');

        $response = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertStringContainsString('Accept', $response->headers->get('Vary'));
    }

    public function testFmAutoIsReplacedWithANegotiatedFormatBeforeGlideSeesIt()
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', 'image/webp,*/*');

        $response = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    public function testFmAutoWithoutAnAcceptHeaderFallsBackToJpgNotHeic()
    {
        $response = $this->controller()->__invoke(Request::create('/images/hero.jpg?w=10&fm=auto'), 'hero.jpg');

        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testFmAutoWithAWildcardAcceptFallsBackToJpgNotHeic()
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', '*/*');

        $response = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testFmAutoWithAnImageWildcardAcceptFallsBackToJpgNotHeic()
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', 'image/*');

        $response = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testAConcreteFormatIsPassedThroughUnchanged()
    {
        $response = $this->controller()->__invoke(Request::create('/images/hero.jpg?w=10&fm=png'), 'hero.jpg');

        self::assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function testUnsignedModeIsUnaffectedByARequestWithoutASignature()
    {
        $response = $this->controller()->__invoke(Request::create('/images/hero.jpg?w=10&fm=png'), 'hero.jpg');

        self::assertSame(200, $response->getStatusCode());
    }

    public function testAValidSignaturePasses()
    {
        $url = new GlideProvider('/images', self::SIGN_KEY)->generateUrl(new ImageTransformation('hero.jpg', width: 10, format: 'png'));

        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))->__invoke(Request::create($url), 'hero.jpg');

        self::assertSame(200, $response->getStatusCode());
    }

    public function testATamperedParameterGives403()
    {
        $url = new GlideProvider('/images', self::SIGN_KEY)->generateUrl(new ImageTransformation('hero.jpg', width: 10, format: 'png'));
        $tampered = str_replace('w=10', 'w=9999', $url);

        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))->__invoke(Request::create($tampered), 'hero.jpg');

        self::assertSame(403, $response->getStatusCode());
    }

    public function testAMissingSignatureGives403WhenAKeyIsConfigured()
    {
        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))
            ->__invoke(Request::create('/images/hero.jpg?w=10&fm=png'), 'hero.jpg');

        self::assertSame(403, $response->getStatusCode());
    }

    public function testASignedFmAutoRequestStillValidatesAndStillNegotiates()
    {
        $url = new GlideProvider('/images', self::SIGN_KEY)->generateUrl(new ImageTransformation('hero.jpg', width: 10, format: 'auto'));

        self::assertStringContainsString('fm=auto', $url);

        $request = Request::create($url);
        $request->headers->set('Accept', 'image/webp,*/*');

        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))->__invoke($request, 'hero.jpg');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    public function testAMissingImageThrowsNotFoundNotAServerError()
    {
        try {
            $this->controller()->__invoke(Request::create('/images/missing.jpg?w=10'), 'missing.jpg');
            self::fail('Expected a NotFoundHttpException.');
        } catch (NotFoundHttpException $e) {
            self::assertSame(404, $e->getStatusCode());
            self::assertSame(['Vary' => 'Accept'], $e->getHeaders());
        }
    }

    private function controller(?SignatureInterface $signature = null): GlideController
    {
        $server = ServerFactory::create([
            'source' => $this->source,
            'cache' => $this->cache,
            'response' => new SymfonyResponseFactory(),
        ]);

        return new GlideController($server, signature: $signature);
    }
}
