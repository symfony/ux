<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface EditorUploadHandlerInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array{url: string, size: int, type?: string, width?: int, height?: int}
     */
    public function handle(UploadedFile $file, array $context = []): array;
}
