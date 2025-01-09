<?php

namespace Symfony\UX\LiveComponent;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @author Simon André <smn.andre@gmail.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveResponse
{
    /**
     * @param string|\SplFileInfo $file The file to send as a response
     * @param string|null $filename    The name of the file to send     (defaults to the basename of the file)
     * @param string|null $contentType The content type of the file     (defaults to `application/octet-stream`)
     */
    public static function file(string|\SplFileInfo $file, ?string $filename = null, ?string $contentType = null, ?int $size = null): BinaryFileResponse
    {
        return new BinaryFileResponse($file, 200, [
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename ?? basename($file)),
            'Content-Type' => $contentType ?? 'application/octet-stream',
            'Content-Length' => $size ?? ($file instanceof \SplFileInfo ? $file->getSize() : null),
        ]);
    }

    /**
     * @param resource|Closure $file   The file to stream as a response
     * @param string $filename         The name of the file to send     (defaults to the basename of the file)
     * @param string|null $contentType The content type of the file     (defaults to `application/octet-stream`)
     * @param int|null $size           The size of the file
     */
    public static function streamFile(mixed $file, string $filename, ?string $contentType = null, ?int $size = null): StreamedResponse
    {
        if (!is_resource($file) && !$file instanceof \Closure) {
            throw new \InvalidArgumentException(sprintf('The file must be a resource or a closure, "%s" given.', get_debug_type($file)));
        }

        return new StreamedResponse($file instanceof \Closure ? $file(...) : function () use ($file) {
            while (!feof($file)) {
                echo fread($file, 1024);
            }
        }, 200, [
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
            'Content-Type' => $contentType ?? 'application/octet-stream',
            'Content-Length' => $size,
        ]);
    }
}
