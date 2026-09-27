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
use Symfony\UX\Image\Bridge\Glide\EncodableFormats;
use Symfony\UX\Image\Bridge\Glide\FormatNegotiator;
use Symfony\UX\Image\Bridge\Glide\GlideProvider;
use Symfony\UX\Image\Provider\PathEncoder;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class GlideController
{
    /** @var list<string> */
    private readonly array $supportedFormats;

    /**
     * @param list<string> $supportedFormats the application's configured formats, in preference order
     */
    public function __construct(
        private readonly Server $server,
        private readonly FormatNegotiator $formatNegotiator = new FormatNegotiator(),
        array $supportedFormats = GlideProvider::SUPPORTED_FORMATS,
        private readonly ?SignatureInterface $signature = null,
    ) {
        $driver = EncodableFormats::driverOf($server->getApi()->getImageManager());
        $this->supportedFormats = EncodableFormats::filter($driver, array_values(array_intersect($supportedFormats, GlideProvider::SUPPORTED_FORMATS)));
    }

    public function __invoke(Request $request, string $path): Response
    {
        $params = $request->query->all();

        if (null !== $this->signature) {
            // Must validate the full prefixed request path before "fm=auto" is rewritten below, or every signed URL 403s.
            try {
                $this->signature->validateRequest($request->getBaseUrl().$request->getPathInfo(), $params);
            } catch (SignatureException) {
                $response = new Response('Forbidden', Response::HTTP_FORBIDDEN);
                $response->headers->set('Vary', 'Accept');

                return $response;
            }
        }

        // Only what the provider generates reaches Glide: an "expand" border, for one, would grow the output past max_image_size.
        $params = array_intersect_key($params, array_flip([...GlideProvider::TRANSFORMATION_PARAMETERS, ...GlideProvider::SUPPORTED_OPERATIONS]));

        if ('auto' === ($params['fm'] ?? null)) {
            $params['fm'] = GlideProvider::toGlideFormat(
                $this->formatNegotiator->negotiate($request->headers->get('Accept'), $this->supportedFormats, 'jpeg'),
            );
        }

        try {
            // The router already decoded $path, and Glide decodes it again: encode it back so a literal "%" survives.
            $response = $this->server->getImageResponse(PathEncoder::encode($path), $params);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException(previous: $e, headers: ['Vary' => 'Accept']);
        }

        $response->headers->set('Vary', 'Accept');
        $response->isNotModified($request);

        return $response;
    }
}
