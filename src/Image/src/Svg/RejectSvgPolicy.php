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
use Symfony\UX\Image\Exception\ImageProcessingException;

final class RejectSvgPolicy implements SvgPolicyInterface
{
    public function process(UploadedFile $file): UploadedFile
    {
        throw ImageProcessingException::unsupportedFormat('svg (SVG is rejected by default)');
    }
}
