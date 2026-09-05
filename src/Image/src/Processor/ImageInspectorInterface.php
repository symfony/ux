<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Processor;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;

/**
 * Interface for image inspection operations.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface ImageInspectorInterface
{
    /**
     * Return best-effort compatibility metadata without applying limits.
     *
     * Missing or unrecognized files use null fields. Use inspectImage() when
     * processing requires a supported, validated image.
     *
     * @return array{width: ?int, height: ?int, mime: ?string, format: ?string}
     */
    public function inspect(string|File|UploadedFile $file): array;

    /**
     * Return trusted metadata or reject missing, unsupported or over-limit input.
     *
     * @throws ExceptionInterface
     */
    public function inspectImage(string|File|UploadedFile $file, ?ProcessingLimits $limits = null): InspectedImage;
}
