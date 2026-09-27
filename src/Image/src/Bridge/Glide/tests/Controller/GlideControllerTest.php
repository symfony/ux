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
use PHPUnit\Framework\Attributes\TestWith;
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
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->source);
    }

    public function testTheControllerSetsVaryAccept(): void
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', 'image/avif,image/webp,*/*');

        $response = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertStringContainsString('Accept', $response->headers->get('Vary'));
    }

    public function testFmAutoIsReplacedWithANegotiatedFormatBeforeGlideSeesIt(): void
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', 'image/webp,*/*');

        $response = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    public function testFmAutoWithoutAnAcceptHeaderFallsBackToJpgNotHeic(): void
    {
        $response = $this->controller()->__invoke(Request::create('/images/hero.jpg?w=10&fm=auto'), 'hero.jpg');

        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testFmAutoWithAWildcardAcceptFallsBackToJpgNotHeic(): void
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', '*/*');

        $response = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testFmAutoWithAnImageWildcardAcceptFallsBackToJpgNotHeic(): void
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', 'image/*');

        $response = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testAConfiguredFormatGlideCannotEncodeIsNeverNegotiated(): void
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', 'image/tiff,*/*');

        $response = $this->controller(supportedFormats: ['tiff', 'webp'])->__invoke($request, 'hero.jpg');

        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testAConfiguredFormatGlideCanEncodeIsStillNegotiated(): void
    {
        $request = Request::create('/images/hero.jpg?w=10&fm=auto');
        $request->headers->set('Accept', 'image/webp,*/*');

        $response = $this->controller(supportedFormats: ['tiff', 'webp'])->__invoke($request, 'hero.jpg');

        self::assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    public function testAConcreteFormatIsPassedThroughUnchanged(): void
    {
        $response = $this->controller()->__invoke(Request::create('/images/hero.jpg?w=10&fm=png'), 'hero.jpg');

        self::assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function testUnsignedModeIsUnaffectedByARequestWithoutASignature(): void
    {
        $response = $this->controller()->__invoke(Request::create('/images/hero.jpg?w=10&fm=png'), 'hero.jpg');

        self::assertSame(200, $response->getStatusCode());
    }

    #[TestWith(['a%20b.jpg'])]
    #[TestWith(['café.jpg'])]
    public function testTheRoutePathIsDecodedOnlyOnce(string $filename): void
    {
        copy($this->source.'/hero.jpg', $this->source.'/'.$filename);

        $response = $this->controller()->__invoke(Request::create('/images/x?w=10'), $filename);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testARevalidationWithAMatchingEtagGets304(): void
    {
        $first = $this->controller()->__invoke(Request::create('/images/hero.jpg?w=10'), 'hero.jpg');

        $request = Request::create('/images/hero.jpg?w=10', server: ['HTTP_IF_NONE_MATCH' => $first->getEtag()]);
        $second = $this->controller()->__invoke($request, 'hero.jpg');

        self::assertSame(304, $second->getStatusCode());
    }

    public function testAValidSignaturePasses(): void
    {
        $url = new GlideProvider('/images', self::SIGN_KEY)->generateUrl(new ImageTransformation('hero.jpg', width: 10, format: 'png'));

        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))->__invoke(Request::create($url), 'hero.jpg');

        self::assertSame(200, $response->getStatusCode());
    }

    public function testAValidSignaturePassesWhenTheAppIsServedUnderASubPath(): void
    {
        $url = new GlideProvider('/app/images', self::SIGN_KEY)->generateUrl(new ImageTransformation('hero.jpg', width: 10, format: 'png'));
        $request = Request::create($url, server: ['SCRIPT_FILENAME' => '/var/www/app/index.php', 'SCRIPT_NAME' => '/app/index.php']);

        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))->__invoke($request, 'hero.jpg');

        self::assertSame('/app', $request->getBaseUrl());
        self::assertSame(200, $response->getStatusCode());
    }

    public function testATamperedParameterGives403(): void
    {
        $url = new GlideProvider('/images', self::SIGN_KEY)->generateUrl(new ImageTransformation('hero.jpg', width: 10, format: 'png'));
        $tampered = str_replace('w=10', 'w=9999', $url);

        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))->__invoke(Request::create($tampered), 'hero.jpg');

        self::assertSame(403, $response->getStatusCode());
    }

    public function testAMissingSignatureGives403WhenAKeyIsConfigured(): void
    {
        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))
            ->__invoke(Request::create('/images/hero.jpg?w=10&fm=png'), 'hero.jpg');

        self::assertSame(403, $response->getStatusCode());
    }

    public function testASignedFmAutoRequestStillValidatesAndStillNegotiates(): void
    {
        $url = new GlideProvider('/images', self::SIGN_KEY)->generateUrl(new ImageTransformation('hero.jpg', width: 10, format: 'auto'));

        self::assertStringContainsString('fm=auto', $url);

        $request = Request::create($url);
        $request->headers->set('Accept', 'image/webp,*/*');

        $response = $this->controller(signature: SignatureFactory::create(self::SIGN_KEY))->__invoke($request, 'hero.jpg');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/webp', $response->headers->get('Content-Type'));
    }

    public function testAMissingImageThrowsNotFoundNotAServerError(): void
    {
        try {
            $this->controller()->__invoke(Request::create('/images/missing.jpg?w=10'), 'missing.jpg');
            self::fail('Expected a NotFoundHttpException.');
        } catch (NotFoundHttpException $e) {
            self::assertSame(404, $e->getStatusCode());
            self::assertSame(['Vary' => 'Accept'], $e->getHeaders());
        }
    }

    /**
     * @param list<string>|null $supportedFormats
     */
    private function controller(?SignatureInterface $signature = null, ?array $supportedFormats = null): GlideController
    {
        $server = ServerFactory::create([
            'source' => $this->source,
            'cache' => $this->cache,
            'response' => new SymfonyResponseFactory($this->cache),
        ]);

        return null === $supportedFormats
            ? new GlideController($server, signature: $signature)
            : new GlideController($server, supportedFormats: $supportedFormats, signature: $signature);
    }
}
