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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Url\SymfonyUploadUrlGenerator;

#[CoversClass(SymfonyUploadUrlGenerator::class)]
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

    public function testRejectsNonPositiveSignatureExpiry(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 0);
    }

    public function testGeneratedUrlIsVerifiable(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $url = $generator->generateUploadUrl('upload-verify');
        $request = Request::create($url);

        self::assertTrue($generator->verifyRequest($request));
    }

    public function testVerifyRequestRejectsTamperedUrl(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $url = $generator->generateUploadUrl('upload-tamper');
        $tamperedUrl = str_replace('_expiration=', 'extra=1&_expiration=', $url);
        $request = Request::create($tamperedUrl);

        self::assertFalse($generator->verifyRequest($request));
    }

    public function testVerifyRequestRejectsExpiredUrl(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner);
        $url = $this->uriSigner->sign('http://localhost/upload/upload-expired', time() - 1);
        $request = Request::create($url);

        self::assertFalse($generator->verifyRequest($request));
    }

    public function testVerifyRequestRejectsUnsignedUrl(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $request = Request::create('http://localhost/upload/upload-123?_expiration='.time() + 3600);

        self::assertFalse($generator->verifyRequest($request));
    }

    public function testUrlDoesNotExposeUploadMetadata(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner, 3600);

        $url = $generator->generateUploadUrl('upload-special');

        self::assertSame(['_expiration', '_hash'], array_keys(Request::create($url)->query->all()));
    }

    public function testDefaultSignatureExpiryIsOneHour(): void
    {
        $generator = new SymfonyUploadUrlGenerator($this->urlGenerator, $this->uriSigner);

        $url = $generator->generateUploadUrl('upload-default');

        $query = parse_url($url, \PHP_URL_QUERY);
        self::assertIsString($query);
        parse_str($query, $params);
        $expires = (int) ($params['_expiration'] ?? 0);

        // Should be approximately 1 hour in the future
        self::assertGreaterThan(time() + 3500, $expires);
        self::assertLessThan(time() + 3700, $expires);
    }
}
