<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Token;

use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\UX\Upload\Exception\UploadException;
use Symfony\UX\Upload\Security\AnonymousUploadContextResolver;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;

/**
 * Signs immutable completed-upload metadata without reading stored bytes.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class UploadTokenHandler
{
    private const int MAX_TOKEN_LENGTH = 8192;
    private CompletedUploadAccess $access;

    public function __construct(
        private UriSigner $signer,
        StorageInterface|CompletedUploadAccess $access,
        private int $ttl = 86400,
        private UploadContextResolverInterface $contextResolver = new AnonymousUploadContextResolver(),
        private string $completedPrefix = '.tmp/completed',
    ) {
        $this->access = $access instanceof CompletedUploadAccess ? $access : new CompletedUploadAccess($access);
        $prefix = trim($this->completedPrefix, '/');
        if ('' === $prefix
            || str_starts_with($this->completedPrefix, '/')
            || str_contains($this->completedPrefix, "\0")
            || \in_array('..', explode('/', $prefix), true)
        ) {
            throw new \InvalidArgumentException('The completed upload prefix must be a non-empty relative storage path without ".." segments.');
        }
        if ($this->ttl < 1) {
            throw new \InvalidArgumentException('The upload token TTL must be greater than zero.');
        }
    }

    public function generate(CompletedUpload $upload, ?UploadContext $context = null): string
    {
        $context ??= $this->contextResolver->resolve();
        if (!$context->matches($upload->getOwnerId(), $upload->getTenantId(), $upload->getFieldName())) {
            throw new UploadException('Cannot issue a token for a completed upload owned by another upload context.');
        }
        $this->assertTemporaryPath($upload->getTemporaryPath());

        $tokenExpiresAt = min($upload->expiresAt->getTimestamp(), time() + $this->ttl);

        return substr($this->signer->sign('?'.http_build_query(array_filter([
            'i' => $upload->id,
            'u' => $upload->uploader,
            'p' => $upload->getTemporaryPath(),
            'f' => $upload->originalName,
            'm' => $upload->mimeType,
            's' => $upload->size,
            'c' => $upload->createdAt->getTimestamp(),
            'e' => $upload->expiresAt->getTimestamp(),
            'x' => $tokenExpiresAt,
            'h' => $upload->checksum,
            'a' => $upload->checksumAlgorithm,
            'o' => $upload->getOwnerId(),
            't' => $upload->getTenantId(),
            'd' => $upload->getFieldName(),
        ], static fn (mixed $value): bool => null !== $value)), $tokenExpiresAt), 1);
    }

    public function resolve(?string $token, ?UploadContext $context = null): ?CompletedUpload
    {
        return $this->resolveToken($token, $context, false);
    }

    /**
     * Resolve a token for an explicitly authorized non-Form target.
     *
     * Live Component targets replace the Form field binding with their
     * #[UploadTarget] allow-list. Owner and tenant binding remain mandatory.
     */
    public function resolveForLiveTarget(?string $token, ?string $uploader = null): ?CompletedUpload
    {
        $upload = $this->resolveToken($token, null, true);
        if (null !== $upload && null !== $uploader && !hash_equals($uploader, $upload->uploader)) {
            return null;
        }

        return $upload;
    }

    private function resolveToken(?string $token, ?UploadContext $context, bool $acceptAnyField): ?CompletedUpload
    {
        if (null === $token || '' === $token || \strlen($token) > self::MAX_TOKEN_LENGTH || !$this->signer->check('?'.$token)) {
            return null;
        }

        parse_str($token, $parsedPayload);
        $payload = [];
        foreach ($parsedPayload as $key => $value) {
            if (!\is_string($key) || !\is_string($value)) {
                return null;
            }
            $payload[$key] = $value;
        }
        if (!$this->hasRequiredPayload($payload)) {
            return null;
        }

        $expiresAt = (int) $payload['e'];
        if ($expiresAt < time() || (int) $payload['x'] < time()) {
            return null;
        }
        $path = $payload['p'];
        if (!$this->isTemporaryPath($path)) {
            return null;
        }

        $ownerId = $this->nullableString($payload, 'o');
        $tenantId = $this->nullableString($payload, 't');
        $fieldName = $this->nullableString($payload, 'd');
        $context ??= $this->contextResolver->resolve();
        if ($acceptAnyField) {
            $context = new UploadContext($context->ownerId, $context->tenantId, $fieldName);
        }
        if (!$context->matches($ownerId, $tenantId, $fieldName)) {
            return null;
        }

        $checksum = $this->nullableString($payload, 'h');
        $checksumAlgorithm = $this->nullableString($payload, 'a');
        if ((null === $checksum) !== (null === $checksumAlgorithm)) {
            return null;
        }

        try {
            return new CompletedUpload(
                id: $payload['i'],
                uploader: $payload['u'],
                path: $path,
                originalName: $payload['f'],
                mimeType: $payload['m'],
                size: (int) $payload['s'],
                createdAt: new \DateTimeImmutable()->setTimestamp((int) $payload['c']),
                expiresAt: new \DateTimeImmutable()->setTimestamp($expiresAt),
                checksum: $checksum,
                checksumAlgorithm: $checksumAlgorithm,
                ownerId: $ownerId,
                tenantId: $tenantId,
                fieldName: $fieldName,
                access: $this->access,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hasRequiredPayload(array $payload): bool
    {
        foreach (['i', 'u', 'p', 'f', 'm', 's', 'c', 'e', 'x'] as $key) {
            if (!isset($payload[$key]) || !\is_string($payload[$key]) || '' === $payload[$key]) {
                return false;
            }
        }

        return ctype_digit($payload['s']) && ctype_digit($payload['c']) && ctype_digit($payload['e']) && ctype_digit($payload['x']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function nullableString(array $payload, string $key): ?string
    {
        return isset($payload[$key]) && \is_string($payload[$key]) && '' !== $payload[$key] ? $payload[$key] : null;
    }

    private function assertTemporaryPath(string $path): void
    {
        if (!$this->isTemporaryPath($path)) {
            throw new UploadException(\sprintf('Completed upload path "%s" is outside UX Upload temporary storage.', $path));
        }
    }

    private function isTemporaryPath(string $path): bool
    {
        $prefix = trim($this->completedPrefix, '/');

        return '' !== $prefix
            && !str_starts_with($path, '/')
            && !str_contains($path, "\0")
            && !\in_array('..', explode('/', $path), true)
            && str_starts_with($path, $prefix.'/');
    }
}
