<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide;

use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Turns a cached Glide image into an HttpFoundation response, since league/glide only ships a PSR-7 factory.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class SymfonyResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        private readonly string $cacheDirectory,
        private readonly int $maxAge = 31536000,
        private readonly bool $public = true,
    ) {
    }

    public function create(FilesystemOperator $cache, string $path): BinaryFileResponse
    {
        // A file response lets the web server send the bytes (X-Sendfile) and gives the browser validators to revalidate with.
        $response = new BinaryFileResponse($this->cacheDirectory.'/'.$path, 200, ['Content-Type' => $cache->mimeType($path)], $this->public, null, true, true);
        $response->setCache(['max_age' => $this->maxAge, 'public' => $this->public]);
        $response->setExpires(new \DateTimeImmutable('@'.(time() + $this->maxAge)));

        return $response;
    }
}
