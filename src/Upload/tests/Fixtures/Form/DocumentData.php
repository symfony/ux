<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Fixtures\Form;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Application data written for FileType: it only knows the HttpFoundation class.
 */
final class DocumentData
{
    #[Assert\File(maxSize: '1k', mimeTypes: ['text/plain'])]
    public ?UploadedFile $document = null;
}
