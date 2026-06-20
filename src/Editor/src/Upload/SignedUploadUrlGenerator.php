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

use Symfony\UX\Editor\Exception\Upload\InvalidSignatureException;

final class SignedUploadUrlGenerator
{
    public function __construct(
        #[\SensitiveParameter] private readonly string $secret,
        private readonly int $ttlSeconds = 3600,
    ) {
    }

    public function sign(string $field, string $profile, ?int $now = null): string
    {
        $now ??= time();
        $exp = $now + $this->ttlSeconds;
        $payload = \sprintf('%s|%s|%d', $field, $profile, $exp);
        $sig = hash_hmac('sha256', $payload, $this->secret);

        return base64_encode($payload.'|'.$sig);
    }

    public function verify(string $token, string $field, string $profile, ?int $now = null): void
    {
        $now ??= time();
        $decoded = base64_decode($token, true);
        if (false === $decoded) {
            throw new InvalidSignatureException('Invalid token encoding');
        }
        $parts = explode('|', $decoded);
        if (4 !== \count($parts)) {
            throw new InvalidSignatureException('Invalid token shape');
        }
        [$f, $p, $exp, $sig] = $parts;
        $payload = \sprintf('%s|%s|%s', $f, $p, $exp);
        $expected = hash_hmac('sha256', $payload, $this->secret);
        if (!hash_equals($expected, $sig)) {
            throw new InvalidSignatureException('Signature mismatch');
        }
        if ($f !== $field) {
            throw new InvalidSignatureException('Field mismatch');
        }
        if ($p !== $profile) {
            throw new InvalidSignatureException('Profile mismatch');
        }
        if ((int) $exp <= $now) {
            throw new InvalidSignatureException('Token expired');
        }
    }
}
