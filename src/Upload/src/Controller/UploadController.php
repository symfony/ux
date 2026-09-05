<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Controller;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;
use Symfony\UX\Upload\Exception\ValidationException;
use Symfony\UX\Upload\Policy\UploadPolicy;
use Symfony\UX\Upload\Policy\UploadPolicySigner;
use Symfony\UX\Upload\Security\AnonymousUploadContextResolver;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Token\ResumeTokenHandler;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\PendingUpload;
use Symfony\UX\Upload\UploaderInterface;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UploadController
{
    public function __construct(
        private readonly UploaderInterface $uploader,
        private readonly UploadUrlGeneratorInterface $urlGenerator,
        private readonly UploadTokenHandler $tokenHandler,
        private readonly StorageInterface $storage,
        private readonly ?ContainerInterface $uploaders = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?CsrfTokenManagerInterface $csrfTokenManager = null,
        private readonly ?RateLimiterFactoryInterface $initRateLimiter = null,
        private readonly UploadContextResolverInterface $contextResolver = new AnonymousUploadContextResolver(),
        private readonly ?UploadPolicySigner $policySigner = null,
        private readonly ?ResumeTokenHandler $resumeTokenHandler = null,
        private readonly bool $allowAnonymous = false,
    ) {
    }

    /**
     * Initialize an upload session.
     */
    public function init(Request $request): Response
    {
        // Throttle before parsing the body so init spam (session/resource exhaustion,
        // signed-URL brute forcing by volume) is rejected cheaply. Opt-in: the
        // limiter is null unless "ux_upload.rate_limiter" names a service, so default
        // installs are never throttled. Keyed per client IP.
        if ($this->isInitializationRateLimited($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $payload = $request->toArray();

            if (!$this->isCsrfValid($request)) {
                return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
            }

            if ($denied = $this->denyAnonymousUpload()) {
                return $denied;
            }

            /** @var array{filename?: string, fileSize?: int, mimeType?: string, uploader?: string, hash?: string, hashAlgorithm?: string, policyToken?: string} $payload */
            $filename = $payload['filename'] ?? throw new InvalidArgumentException('Missing "filename" field in request body.');
            $fileSize = $payload['fileSize'] ?? throw new InvalidArgumentException('Missing "fileSize" field in request body.');
            $mimeType = $payload['mimeType'] ?? 'application/octet-stream';
            $hash = isset($payload['hash']) && \is_string($payload['hash']) ? $payload['hash'] : null;
            $hashAlgorithm = isset($payload['hashAlgorithm']) && \is_string($payload['hashAlgorithm']) ? $payload['hashAlgorithm'] : null;

            $policy = isset($payload['policyToken']) && \is_string($payload['policyToken']) ? $this->policySigner?->resolve($payload['policyToken']) : null;
            if (isset($payload['policyToken']) && null === $policy) {
                return new JsonResponse(['error' => 'Invalid or expired upload policy'], Response::HTTP_FORBIDDEN);
            }
            $uploaderName = null !== $policy ? $policy->uploader : ($payload['uploader'] ?? null);
            $uploader = $this->resolveUploader($uploaderName);
            if (null !== $policy) {
                if ($policy->maxSize > 0 && (int) $fileSize > $policy->maxSize) {
                    throw new ValidationException(\sprintf('File size exceeds form policy limit of %d bytes.', $policy->maxSize));
                }
                if (!$policy->allows($mimeType)) {
                    throw new ValidationException(\sprintf('MIME type "%s" is not allowed by the form policy.', $mimeType));
                }
            }

            $context = $this->getUploadContext($policy);
            $pending = $uploader->initializeUpload($filename, (int) $fileSize, $mimeType, $hash, $hashAlgorithm, $context, $policy);
            $uploadUrl = $this->urlGenerator->generateUploadUrl($pending->uploadId);

            return new JsonResponse([
                'uploadId' => $pending->uploadId,
                'uploadUrl' => $uploadUrl,
                'config' => [
                    'totalChunks' => $pending->totalChunks,
                    'chunkSize' => $pending->chunkSize,
                    'compression' => $pending->compression,
                    'parallel' => $pending->parallel,
                ],
                'resumeToken' => $this->resumeTokenHandler?->generate($pending->uploadId, $context),
            ]);
        } catch (InvalidArgumentException|ValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (RequestExceptionInterface) {
            return new JsonResponse(['error' => 'Malformed request body'], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger?->error('Upload init failed: {message}', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload and complete a file in one request when it fits in one configured chunk.
     */
    public function direct(Request $request): Response
    {
        if ($this->isInitializationRateLimited($request)) {
            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            if (!$this->isCsrfValid($request)) {
                return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
            }

            if ($denied = $this->denyAnonymousUpload()) {
                return $denied;
            }

            $file = $request->files->get('file');
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                throw new InvalidArgumentException('Missing or invalid "file" upload.');
            }

            $filename = $request->request->getString('filename', $file->getClientOriginalName());
            $fileSize = filter_var($request->request->get('fileSize'), \FILTER_VALIDATE_INT);
            if (false === $fileSize) {
                throw new InvalidArgumentException('Missing or invalid "fileSize" field.');
            }
            $mimeType = $request->request->getString('mimeType', 'application/octet-stream');
            $hash = $this->nullableRequestString($request, 'hash');
            $hashAlgorithm = $this->nullableRequestString($request, 'hashAlgorithm');
            $digest = $this->nullableRequestString($request, 'digest');
            $policyToken = $this->nullableRequestString($request, 'policyToken');
            $policy = null !== $policyToken ? $this->policySigner?->resolve($policyToken) : null;
            if (null !== $policyToken && null === $policy) {
                return new JsonResponse(['error' => 'Invalid or expired upload policy'], Response::HTTP_FORBIDDEN);
            }

            $requestedUploader = $this->nullableRequestString($request, 'uploader');
            $uploader = $this->resolveUploader(null !== $policy ? $policy->uploader : $requestedUploader);
            if (null !== $policy) {
                if ($policy->maxSize > 0 && $fileSize > $policy->maxSize) {
                    throw new ValidationException(\sprintf('File size exceeds form policy limit of %d bytes.', $policy->maxSize));
                }
                if (!$policy->allows($mimeType)) {
                    throw new ValidationException(\sprintf('MIME type "%s" is not allowed by the form policy.', $mimeType));
                }
            }

            $transmittedSize = $file->getSize();
            $chunkSize = $uploader->getConfig()['chunk_size'];
            if ($fileSize > $chunkSize) {
                return new JsonResponse(
                    ['error' => \sprintf('Direct upload size %d bytes exceeds the single-request limit of %d bytes.', $fileSize, $chunkSize)],
                    Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
                );
            }

            $maxTransmittedSize = max($chunkSize + 64 * 1024, (int) ceil($chunkSize * 1.1));
            if (false === $transmittedSize || $transmittedSize > $maxTransmittedSize) {
                return new JsonResponse(
                    ['error' => \sprintf('Direct upload body exceeds the single-request limit of %d bytes.', $chunkSize)],
                    Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
                );
            }

            $context = $this->getUploadContext($policy);
            $result = $uploader->uploadDirect(
                $filename,
                $fileSize,
                $mimeType,
                $file->getContent(),
                $hash,
                $hashAlgorithm,
                $digest,
                $context,
                $policy,
                'gzip' === $this->nullableRequestString($request, 'contentEncoding'),
            );

            return $this->completedResponse($result, $context);
        } catch (InvalidArgumentException|ValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger?->error('Direct upload failed: {message}', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resume(Request $request): Response
    {
        try {
            if (!$this->isCsrfValid($request)) {
                return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
            }

            if ($denied = $this->denyAnonymousUpload()) {
                return $denied;
            }
            $payload = $request->toArray();
            $token = $payload['resumeToken'] ?? null;
            if (!\is_string($token) || null === $this->resumeTokenHandler) {
                return new JsonResponse(['error' => 'Invalid resume token'], Response::HTTP_FORBIDDEN);
            }
            $policy = isset($payload['policyToken']) && \is_string($payload['policyToken']) ? $this->policySigner?->resolve($payload['policyToken']) : null;
            if (isset($payload['policyToken']) && null === $policy) {
                return new JsonResponse(['error' => 'Invalid or expired upload policy'], Response::HTTP_FORBIDDEN);
            }
            $context = $this->getUploadContext($policy);
            $uploadId = $this->resumeTokenHandler->resolve($token, $context);
            if (null === $uploadId) {
                return new JsonResponse(['error' => 'Invalid or expired resume token'], Response::HTTP_FORBIDDEN);
            }
            $metadata = $this->storage->getMetadata($uploadId);
            if (null === $metadata) {
                return new JsonResponse(['error' => 'Upload session not found'], Response::HTTP_NOT_FOUND);
            }
            if (!$context->matches(
                \is_string($metadata['ownerId'] ?? null) ? $metadata['ownerId'] : null,
                \is_string($metadata['tenantId'] ?? null) ? $metadata['tenantId'] : null,
                \is_string($metadata['field'] ?? null) ? $metadata['field'] : null,
            )) {
                return new JsonResponse(['error' => 'Upload does not belong to the current upload context'], Response::HTTP_FORBIDDEN);
            }
            $uploader = $this->resolveUploaderForUpload($uploadId);
            $config = $uploader->getConfig();
            $filename = isset($metadata['filename']) && \is_string($metadata['filename']) ? $metadata['filename'] : '';
            $fileSize = isset($metadata['fileSize']) && \is_int($metadata['fileSize']) ? $metadata['fileSize'] : 0;
            $mimeType = isset($metadata['mimeType']) && \is_string($metadata['mimeType']) ? $metadata['mimeType'] : 'application/octet-stream';
            $totalChunks = isset($metadata['totalChunks']) && \is_int($metadata['totalChunks']) ? $metadata['totalChunks'] : 0;
            $parallel = isset($metadata['parallel']) && \is_int($metadata['parallel']) ? $metadata['parallel'] : 1;
            $pending = new PendingUpload(
                $uploadId,
                $filename,
                $fileSize,
                $mimeType,
                $totalChunks,
                $config['chunk_size'],
                (bool) ($metadata['compression'] ?? false),
                $parallel,
            );

            return new JsonResponse([
                'uploadId' => $uploadId,
                'uploadUrl' => $this->urlGenerator->generateUploadUrl($uploadId),
                'resumeToken' => $token,
                'config' => [
                    'totalChunks' => $pending->totalChunks,
                    'chunkSize' => $pending->chunkSize,
                    'compression' => $pending->compression,
                    'parallel' => $pending->parallel,
                ],
            ]);
        } catch (UploadSessionNotFoundException) {
            return new JsonResponse(['error' => 'Upload session not found'], Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException|ValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (RequestExceptionInterface) {
            return new JsonResponse(['error' => 'Malformed request body'], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger?->error('Upload resume failed: {message}', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function handle(Request $request, string $uploadId): Response
    {
        if (!$this->urlGenerator->verifyRequest($request)) {
            return new JsonResponse(['error' => 'Invalid or expired signature'], Response::HTTP_FORBIDDEN);
        }
        if ('GET' !== $request->getMethod() && !$this->isCsrfValid($request)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        try {
            $metadata = $this->storage->getMetadata($uploadId);
            if (null === $metadata) {
                throw new UploadSessionNotFoundException($uploadId);
            }
            $storedFieldName = \is_string($metadata['field'] ?? null) ? $metadata['field'] : null;
            $context = $this->getStoredUploadContext($storedFieldName);
            if (!$context->matches(
                \is_string($metadata['ownerId'] ?? null) ? $metadata['ownerId'] : null,
                \is_string($metadata['tenantId'] ?? null) ? $metadata['tenantId'] : null,
                $storedFieldName,
            )) {
                return new JsonResponse(['error' => 'Upload does not belong to the current upload context'], Response::HTTP_FORBIDDEN);
            }

            // Resolve the uploader from the metadata stored during init
            $uploader = $this->resolveUploaderForUpload($uploadId);

            return match ($request->getMethod()) {
                'GET' => $this->status($uploader, $uploadId),
                'PUT' => $this->chunk($request, $uploader, $uploadId),
                'POST' => $this->complete($uploader, $uploadId, $context),
                'DELETE' => $this->cancel($uploader, $uploadId),
                default => new JsonResponse(['error' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED),
            };
        } catch (UploadSessionNotFoundException) {
            return new JsonResponse(['error' => 'Upload session not found'], Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException|ValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger?->error('Upload operation failed: {message}', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'uploadId' => $uploadId,
                'method' => $request->getMethod(),
            ]);

            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function remove(Request $request): Response
    {
        try {
            if (!$this->isCsrfValid($request)) {
                return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
            }

            if ($denied = $this->denyAnonymousUpload()) {
                return $denied;
            }
            $payload = $request->toArray();
            $policy = isset($payload['policyToken']) && \is_string($payload['policyToken']) ? $this->policySigner?->resolve($payload['policyToken']) : null;
            if (isset($payload['policyToken']) && null === $policy) {
                return new JsonResponse(['error' => 'Invalid or expired upload policy'], Response::HTTP_FORBIDDEN);
            }
            $context = $this->getUploadContext($policy);
            $token = $payload['token'] ?? null;
            if (!\is_string($token) || null === $upload = $this->tokenHandler->resolve($token, $context)) {
                return new JsonResponse(['error' => 'Invalid or expired upload token'], Response::HTTP_NOT_FOUND);
            }
            $upload->delete();

            return new JsonResponse(['success' => true]);
        } catch (RequestExceptionInterface) {
            return new JsonResponse(['error' => 'Malformed request body'], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger?->error('Completed upload removal failed: {message}', [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function status(UploaderInterface $uploader, string $uploadId): Response
    {
        $progress = $uploader->getProgress($uploadId);

        return new JsonResponse([
            'progress' => [
                'storedChunks' => $progress->storedChunks,
                'totalChunks' => $progress->totalChunks,
                'percentComplete' => $progress->percentComplete,
                'chunkIndices' => $progress->chunkIndices,
            ],
        ]);
    }

    private function chunk(Request $request, UploaderInterface $uploader, string $uploadId): Response
    {
        $chunkIndex = $request->headers->get('X-Chunk-Index');
        if (!is_numeric($chunkIndex)) {
            throw new InvalidArgumentException('Missing X-Chunk-Index header.');
        }

        $chunkIndex = (int) $chunkIndex;
        // Validate Content-Length header before reading the body
        $maxChunkSize = $uploader->getConfig()['chunk_size'] ?? 5 * 1024 * 1024;
        $contentLength = $request->headers->get('Content-Length');
        if (null !== $contentLength && (int) $contentLength > $maxChunkSize * 2) {
            throw new InvalidArgumentException(\sprintf('Content-Length %d bytes exceeds maximum allowed chunk size of %d bytes.', (int) $contentLength, $maxChunkSize));
        }

        $content = $request->getContent();

        // Validate actual chunk size (with tolerance for compression headers)
        if (\strlen($content) > $maxChunkSize * 1.1) {
            throw new InvalidArgumentException(\sprintf('Chunk size %d bytes exceeds maximum allowed chunk size of %d bytes.', \strlen($content), $maxChunkSize));
        }

        $digest = $request->headers->get('X-Chunk-Digest');
        $compressed = 'gzip' === strtolower((string) $request->headers->get('Content-Encoding'));
        $uploader->storeChunk($uploadId, (int) $chunkIndex, $content, null !== $digest && '' !== $digest ? $digest : null, $compressed);

        return new JsonResponse(['success' => true]);
    }

    private function complete(UploaderInterface $uploader, string $uploadId, UploadContext $context): Response
    {
        $result = $uploader->completeUpload($uploadId);

        return $this->completedResponse($result, $context);
    }

    private function completedResponse(CompletedUpload $result, UploadContext $context): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'uploadId' => $result->id,
            'token' => $this->tokenHandler->generate($result, $context),
            'meta' => [
                'filename' => $result->originalName,
                'mimeType' => $result->mimeType,
                'size' => $result->size,
            ],
        ]);
    }

    private function isInitializationRateLimited(Request $request): bool
    {
        return null !== $this->initRateLimiter
            && !$this->initRateLimiter->create($request->getClientIp() ?? 'anonymous')->consume(1)->isAccepted();
    }

    private function nullableRequestString(Request $request, string $key): ?string
    {
        $value = $request->request->get($key);

        return \is_string($value) && '' !== $value ? $value : null;
    }

    private function cancel(UploaderInterface $uploader, string $uploadId): Response
    {
        $uploader->cancelUpload($uploadId);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Refuses an upload that Security cannot attribute to anyone.
     *
     * Without an owner or a tenant every visitor shares one UploadContext: the
     * pending quota becomes global, so one actor filling it locks out everyone
     * else. Applications behind a firewall get an owner from Security and never
     * reach this; public endpoints opt in through "ux_upload.allow_anonymous".
     */
    private function denyAnonymousUpload(): ?JsonResponse
    {
        if ($this->allowAnonymous) {
            return null;
        }

        $context = $this->contextResolver->resolve();
        if (null !== $context->ownerId || null !== $context->tenantId) {
            return null;
        }

        $this->logger?->warning('Rejected an upload that has neither an owner nor a tenant. Authenticate the request, register an UploadContextResolverInterface, or set "ux_upload.allow_anonymous" to true to accept anonymous uploads.');

        return new JsonResponse(['error' => 'Anonymous uploads are disabled'], Response::HTTP_FORBIDDEN);
    }

    private function isCsrfValid(Request $request): bool
    {
        return null === $this->csrfTokenManager
            || $this->csrfTokenManager->isTokenValid(new CsrfToken('ux_upload', $request->headers->get('X-CSRF-Token')));
    }

    /**
     * Resolve the uploader by name. Falls back to the default uploader.
     *
     * @throws InvalidArgumentException if the specified uploader is not registered
     */
    private function resolveUploader(?string $name): UploaderInterface
    {
        if (null === $name || 'default' === $name) {
            return $this->uploader;
        }

        if (null !== $this->uploaders && $this->uploaders->has($name)) {
            $uploader = $this->uploaders->get($name);
            if (!$uploader instanceof UploaderInterface) {
                throw new \LogicException(\sprintf('Uploader service "%s" must implement "%s".', $name, UploaderInterface::class));
            }

            return $uploader;
        }

        throw new InvalidArgumentException(\sprintf('Unknown uploader "%s".', $name));
    }

    /**
     * Resolve the correct uploader for an existing upload session from its metadata.
     */
    private function resolveUploaderForUpload(string $uploadId): UploaderInterface
    {
        $metadata = $this->storage->getMetadata($uploadId);
        if (null === $metadata) {
            throw new UploadSessionNotFoundException($uploadId);
        }
        /** @var string $uploaderName */
        $uploaderName = $metadata['uploader'] ?? 'default';

        return $this->resolveUploader($uploaderName);
    }

    private function getUploadContext(?UploadPolicy $policy): UploadContext
    {
        $identity = $this->contextResolver->resolve();

        return new UploadContext(
            $identity->ownerId,
            $identity->tenantId,
            null !== $policy && null !== $policy->fieldName ? $policy->fieldName : $identity->fieldName,
        );
    }

    private function getStoredUploadContext(?string $fieldName): UploadContext
    {
        $identity = $this->contextResolver->resolve();

        return new UploadContext($identity->ownerId, $identity->tenantId, $fieldName);
    }
}
