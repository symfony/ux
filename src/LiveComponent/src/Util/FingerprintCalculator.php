<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\LiveComponentHydrator;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class FingerprintCalculator
{
    public function __construct(
        private NormalizerInterface $objectNormalizer,
        private string $secret,
    ) {
    }

    public function calculateFingerprint(array $data): string
    {
        $normalizedData = $this->objectNormalizer->normalize($data, context: [LiveComponentHydrator::LIVE_CONTEXT => true]);

        return base64_encode(hash_hmac('sha256', serialize($normalizedData), $this->secret, true));
    }
}
