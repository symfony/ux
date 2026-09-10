<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Svg;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\ExceptionInterface;

/**
 * Converts an SVG upload into an inspected-safe raster upload or rejects it.
 */
interface SvgPolicyInterface
{
    /**
     * @throws ExceptionInterface
     */
    public function process(UploadedFile $file): UploadedFile;
}
