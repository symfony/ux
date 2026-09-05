<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide\Controller;

use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Server;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\Image\Bridge\Glide\FormatNegotiator;
use Symfony\UX\Image\Bridge\Glide\GlideProvider;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class GlideController
{
    /**
     * @param list<string> $supportedFormats
     */
    public function __construct(
        private readonly Server $server,
        private readonly FormatNegotiator $formatNegotiator = new FormatNegotiator(),
        private readonly array $supportedFormats = GlideProvider::SUPPORTED_FORMATS,
        private readonly ?SignatureInterface $signature = null,
    ) {
    }

    public function __invoke(Request $request, string $path): Response
    {
        $params = $request->query->all();

        if (null !== $this->signature) {
            // Must validate the full prefixed request path before "fm=auto" is rewritten below, or every signed URL 403s.
            try {
                $this->signature->validateRequest($request->getPathInfo(), $params);
            } catch (SignatureException) {
                $response = new Response('Forbidden', Response::HTTP_FORBIDDEN);
                $response->headers->set('Vary', 'Accept');

                return $response;
            }
        }

        if ('auto' === ($params['fm'] ?? null)) {
            $params['fm'] = GlideProvider::toGlideFormat(
                $this->formatNegotiator->negotiate($request->headers->get('Accept'), $this->supportedFormats, 'jpg'),
            );
        }

        try {
            $response = $this->server->getImageResponse($path, $params);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException(previous: $e, headers: ['Vary' => 'Accept']);
        }

        $response->headers->set('Vary', 'Accept');

        return $response;
    }
}
