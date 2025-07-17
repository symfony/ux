<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Checksum;

/** @internal */
class ChecksumCalculator
{
    public function __construct(private readonly string $secret)
    {
    }

    public function calculateForArray(array $data): string
    {
        $this->sortKeysRecursively($data);

        return base64_encode(hash_hmac('sha256', json_encode($data), $this->secret, true));
    }

    private function sortKeysRecursively(array &$data): void
    {
        foreach ($data as &$value) {
            if (\is_array($value)) {
                $this->sortKeysRecursively($value);
            }
        }
        ksort($data);
    }
}
