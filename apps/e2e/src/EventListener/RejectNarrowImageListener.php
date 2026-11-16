<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\Exception\ValidationException;

/**
 * Custom content validation for the "images" uploader of the UX Upload validation page.
 */
#[AsEventListener(priority: 100)]
final class RejectNarrowImageListener
{
    public const MIN_WIDTH = 16;

    public function __invoke(UploadAssembledEvent $event): void
    {
        $upload = $event->getUpload();
        if ('images' !== $upload->getUploaderName() || 'image/png' !== $upload->getMimeType()) {
            return;
        }

        $stream = $upload->openStream();
        try {
            $header = (string) fread($stream, 24);
        } finally {
            fclose($stream);
        }

        $width = unpack('N', substr($header, 16, 4))[1] ?? 0;
        if ($width < self::MIN_WIDTH) {
            throw new ValidationException(\sprintf('The image must be at least %d pixels wide.', self::MIN_WIDTH));
        }
    }
}
