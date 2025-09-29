<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Document
{
    public ?string $title = null;

    public ?UploadedFile $file = null;

    /**
     * @var list<UploadedFile>
     */
    public array $attachments = [];

    public array $details = [];
}
