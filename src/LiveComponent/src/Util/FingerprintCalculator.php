<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class FingerprintCalculator
{
    public function __construct(private string $secret)
    {
    }

    public function calculateFingerprint(array $data): string
    {
        return base64_encode(hash_hmac('sha256', serialize($data), $this->secret, true));
    }
}
