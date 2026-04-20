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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Editor\Exception\Upload\InvalidSignatureException;
use Symfony\UX\Editor\Exception\Upload\UnsupportedFileException;
use Symfony\UX\Editor\Exception\Upload\UploadHandlerException;

final class EditorUploadController
{
    public function __construct(
        private readonly SignedUploadUrlGenerator $signer,
        private readonly UploadHandlerRegistry $handlers,
        private readonly string $defaultProfile = 'default',
    ) {
    }

    public function __invoke(string $field, Request $request): Response
    {
        $token = (string) $request->query->get('token', '');
        $profile = (string) $request->query->get('profile', $this->defaultProfile);
        try {
            $this->signer->verify($token, $field, $profile);
        } catch (InvalidSignatureException $e) {
            return new JsonResponse(['error' => 'invalid_signature', 'message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'missing_file'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            $result = $this->handlers->get($profile)->handle($file, ['field' => $field, 'profile' => $profile]);
        } catch (UnsupportedFileException $e) {
            return new JsonResponse(['error' => 'unsupported_file', 'message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (UploadHandlerException) {
            return new JsonResponse(['error' => 'handler_error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($result);
    }
}
