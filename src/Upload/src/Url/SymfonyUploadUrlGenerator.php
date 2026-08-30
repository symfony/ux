<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Url;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Upload\Exception\InvalidArgumentException;

/**
 * Default URL generator using Symfony routing and UriSigner.
 *
 * Generates signed URLs pointing to the `ux_upload_handle` route and
 * verifies incoming requests against the UriSigner signature and
 * expiration timestamp.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class SymfonyUploadUrlGenerator implements UploadUrlGeneratorInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UriSigner $uriSigner,
        private int $signatureExpiry = 3600,
    ) {
        if ($signatureExpiry < 1) {
            throw new InvalidArgumentException('The upload URL signature expiry must be at least one second.');
        }
    }

    public function generateUploadUrl(string $uploadId): string
    {
        $url = $this->urlGenerator->generate(
            'ux_upload_handle',
            ['uploadId' => $uploadId],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return $this->uriSigner->sign($url, time() + $this->signatureExpiry);
    }

    public function verifyRequest(Request $request): bool
    {
        return $this->uriSigner->check($request->getUri());
    }
}
