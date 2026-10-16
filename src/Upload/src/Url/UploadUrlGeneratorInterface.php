<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Url;

use Symfony\Component\HttpFoundation\Request;

/**
 * Generates and verifies signed URLs for upload sessions.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface UploadUrlGeneratorInterface
{
    /**
     * Generate the URL where chunks should be uploaded for this session.
     */
    public function generateUploadUrl(string $uploadId): string;

    /**
     * Verify that an incoming request carries valid auth for this URL.
     */
    public function verifyRequest(Request $request): bool;
}
