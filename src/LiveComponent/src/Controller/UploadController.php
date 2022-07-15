<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UploadController
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/');
    }

    public function uploadAction(Request $request): JsonResponse
    {
        $files = [];

        foreach ($request->files->all() as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // TODO: use UUID?
            $name = sprintf('%s.%s', uniqid('live-', true), strtolower($file->getClientOriginalExtension()));

            $file->move($this->uploadDir, $name);

            $files[$file->getClientOriginalName()] = $name;
        }

        return new JsonResponse($files);
    }

    public function previewAction(string $filename): BinaryFileResponse
    {
        try {
            $file = new File("{$this->uploadDir}/{$filename}");
        } catch (FileNotFoundException) {
            throw new NotFoundHttpException(sprintf('File "%s" not found.', $filename));
        }

        if (!str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw new NotFoundHttpException('Only images can be previewed.');
        }

        return new BinaryFileResponse($file);
    }
}
