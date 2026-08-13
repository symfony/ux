<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Policy;

use Symfony\Component\HttpFoundation\UriSigner;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class UploadPolicySigner
{
    private const int MAX_TOKEN_LENGTH = 4096;

    public function __construct(private UriSigner $signer, private int $ttl = 3600)
    {
    }

    /**
     * @param list<string> $allowedTypes
     */
    public function issue(string $uploader, int $maxSize, array $allowedTypes, int $maxFiles, ?string $fieldName = null): string
    {
        $payload = [
            'u' => $uploader,
            's' => $maxSize,
            't' => implode(',', $allowedTypes),
            'n' => $maxFiles,
            'f' => $fieldName ?? '',
            'e' => time() + $this->ttl,
        ];

        return substr($this->signer->sign('?'.http_build_query($payload), $payload['e']), 1);
    }

    public function resolve(string $token): ?UploadPolicy
    {
        if ('' === $token || \strlen($token) > self::MAX_TOKEN_LENGTH || !$this->signer->check('?'.$token)) {
            return null;
        }
        parse_str($token, $data);
        if (!isset($data['u'], $data['s'], $data['t'], $data['n'], $data['f'], $data['e'])
            || !\is_string($data['u']) || !\is_string($data['s']) || !\is_string($data['t'])
            || !\is_string($data['n']) || !\is_string($data['f']) || !\is_string($data['e']) || (int) $data['e'] < time()
        ) {
            return null;
        }

        return new UploadPolicy(
            $data['u'],
            (int) $data['s'],
            '' === $data['t'] ? [] : explode(',', $data['t']),
            (int) $data['n'],
            (int) $data['e'],
            '' === $data['f'] ? null : $data['f'],
        );
    }
}
