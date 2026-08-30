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
use Symfony\UX\Upload\Security\UploadContext;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class ResumeTokenHandler
{
    private const int MAX_TOKEN_LENGTH = 4096;

    public function __construct(private UriSigner $signer, private int $ttl = 86400)
    {
    }

    public function generate(string $uploadId, UploadContext $context): string
    {
        $payload = [
            'k' => 'r',
            'i' => $uploadId,
            'o' => $context->ownerId ?? '',
            't' => $context->tenantId ?? '',
            'f' => $context->fieldName ?? '',
            'e' => time() + $this->ttl,
        ];

        return substr($this->signer->sign('?'.http_build_query($payload), $payload['e']), 1);
    }

    public function resolve(string $token, UploadContext $context): ?string
    {
        if ('' === $token || \strlen($token) > self::MAX_TOKEN_LENGTH || !$this->signer->check('?'.$token)) {
            return null;
        }
        parse_str($token, $payload);
        // All three token types are signed by the same uri_signer, so each payload
        // carries its own kind: a token cannot be replayed as another type.
        if ('r' !== ($payload['k'] ?? null)) {
            return null;
        }
        if (!isset($payload['i'], $payload['o'], $payload['t'], $payload['f'], $payload['e'])
            || !\is_string($payload['i']) || !\is_string($payload['o']) || !\is_string($payload['t']) || !\is_string($payload['f']) || !\is_string($payload['e'])
            || (int) $payload['e'] < time()
            || !hash_equals($payload['o'], $context->ownerId ?? '')
            || !hash_equals($payload['t'], $context->tenantId ?? '')
            || !hash_equals($payload['f'], $context->fieldName ?? '')
        ) {
            return null;
        }

        return $payload['i'];
    }
}
