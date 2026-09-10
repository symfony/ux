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
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a cached Glide image into an HttpFoundation response, since league/glide only ships a PSR-7 factory.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class SymfonyResponseFactory implements ResponseFactoryInterface
{
    public function create(FilesystemOperator $cache, string $path): StreamedResponse
    {
        return new StreamedResponse(static function () use ($cache, $path) {
            $stream = $cache->readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $cache->mimeType($path),
            'Content-Length' => (string) $cache->fileSize($path),
            'Cache-Control' => 'max-age=31536000, public',
            'Expires' => date_create('+1 years')->format('D, d M Y H:i:s').' GMT',
        ]);
    }
}
