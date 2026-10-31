<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Policy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\UX\Upload\Policy\UploadPolicySigner;

final class UploadPolicySignerTest extends TestCase
{
    public function testResolveRejectsATokenSignedForAnotherType(): void
    {
        $uriSigner = new UriSigner('secret');
        // Every key a policy needs, a valid signature, the same secret: only the
        // kind differs. Without the discriminator this would resolve.
        $payload = [
            'k' => 'r',
            'u' => 'documents',
            's' => '1000',
            't' => '',
            'n' => '2',
            'f' => '',
            'e' => (string) (time() + 3600),
        ];
        $token = substr($uriSigner->sign('?'.http_build_query($payload), (int) $payload['e']), 1);

        self::assertNull(new UploadPolicySigner($uriSigner)->resolve($token));
    }

    public function testRoundTrip(): void
    {
        $signer = new UploadPolicySigner(new UriSigner('secret'));

        $policy = $signer->resolve($signer->issue('documents', 1000, ['application/pdf', 'image/*'], 2, 'profile.documents'));

        self::assertSame('documents', $policy?->uploader);
        self::assertSame(1000, $policy?->maxSize);
        self::assertTrue($policy?->allows('image/png'));
        self::assertFalse($policy?->allows('text/plain'));
        self::assertSame(2, $policy?->maxFiles);
        self::assertSame('profile.documents', $policy?->fieldName);
        self::assertSame('documents', $policy?->getUploaderName());
        self::assertSame(1000, $policy?->getMaxSize());
        self::assertSame(['application/pdf', 'image/*'], $policy?->getAllowedTypes());
        self::assertSame(2, $policy?->getMaxFiles());
        self::assertGreaterThan(time(), $policy?->getExpiresAt());
        self::assertSame('profile.documents', $policy?->getFieldName());
    }

    public function testTamperedAndExpiredPoliciesAreRejected(): void
    {
        self::assertNull(new UploadPolicySigner(new UriSigner('secret'))->resolve('invalid'));

        $signer = new UploadPolicySigner(new UriSigner('secret'), -1);
        self::assertNull($signer->resolve($signer->issue('default', 10, [], 1)));
    }

    public function testSignedMalformedPolicyIsRejected(): void
    {
        $expiresAt = time() + 60;
        $uriSigner = new UriSigner('secret');
        $token = substr($uriSigner->sign('?u=default&s[]=10&t=&n=1&f=&e='.$expiresAt, $expiresAt), 1);

        self::assertNull(new UploadPolicySigner($uriSigner)->resolve($token));
    }
}
