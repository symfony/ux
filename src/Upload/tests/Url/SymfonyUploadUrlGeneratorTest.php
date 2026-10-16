<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Url;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Url\SymfonyUploadUrlGenerator;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

final class SymfonyUploadUrlGeneratorTest extends TestCase
{
    private UriSigner $uriSigner;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->uriSigner = new UriSigner('test-secret-key');

        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')
            ->willReturnCallback(static fn (string $route, array $params) => 'http://localhost/upload/'.$params['uploadId']);
    }

    #[Test]
    public function rejectsNonPositiveSignatureExpiry(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 0);
    }

    #[Test]
    public function implementsInterface(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner);

        $this->assertInstanceOf(UploadUrlGeneratorInterface::class, $generator);
    }

    #[Test]
    public function generateUploadUrlReturnsSignedUrl(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $url = $generator->generateUploadUrl('upload-abc');

        $this->assertStringContainsString('http://localhost/upload/upload-abc', $url);
        $this->assertStringContainsString('_expiration=', $url);
        $this->assertStringContainsString('_hash=', $url);
        $this->assertStringNotContainsString('_total=', $url);
        $this->assertStringNotContainsString('_f=', $url);
        $this->assertStringNotContainsString('_s=', $url);
        $this->assertStringNotContainsString('_m=', $url);
    }

    #[Test]
    public function generatedUrlIsVerifiable(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $url = $generator->generateUploadUrl('upload-verify');
        $request = Request::create($url);

        $this->assertTrue($generator->verifyRequest($request));
    }

    #[Test]
    public function verifyRequestRejectsTamperedUrl(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $url = $generator->generateUploadUrl('upload-tamper');
        $tamperedUrl = str_replace('_expiration=', 'extra=1&_expiration=', $url);
        $request = Request::create($tamperedUrl);

        $this->assertFalse($generator->verifyRequest($request));
    }

    #[Test]
    public function verifyRequestRejectsExpiredUrl(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner);
        $url = $this->uriSigner->sign('http://localhost/upload/upload-expired', time() - 1);
        $request = Request::create($url);

        $this->assertFalse($generator->verifyRequest($request));
    }

    #[Test]
    public function verifyRequestRejectsUnsignedUrl(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $request = Request::create('http://localhost/upload/upload-123?_expiration='.time() + 3600);

        $this->assertFalse($generator->verifyRequest($request));
    }

    #[Test]
    public function urlDoesNotExposeUploadMetadata(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $url = $generator->generateUploadUrl('upload-special');

        self::assertSame(['_expiration', '_hash'], array_keys(Request::create($url)->query->all()));
    }

    #[Test]
    public function defaultSignatureExpiryIsOneHour(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner);

        $url = $generator->generateUploadUrl('upload-default');

        parse_str(parse_url($url, \PHP_URL_QUERY) ?? '', $params);
        $expires = (int) ($params['_expiration'] ?? 0);

        // Should be approximately 1 hour in the future
        $this->assertGreaterThan(time() + 3500, $expires);
        $this->assertLessThan(time() + 3700, $expires);
    }
}
