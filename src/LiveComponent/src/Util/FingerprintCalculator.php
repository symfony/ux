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
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;

/**
 * Calculates a fingerprint that is unique to the original input props passed
 * into the component and that also set props that "accept updates from parent".
 *
 * This is used for child components, to determine when the parent has changed
 * certain props that should trigger the child to re-render.
 *
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

    public function calculateFingerprint(array $inputProps, LiveComponentMetadata $liveMetadata): string
    {
        $fingerprintProps = $liveMetadata->getOnlyPropsThatAcceptUpdatesFromParent($inputProps);

        if (0 === \count($fingerprintProps)) {
            return '';
        }

        $normalizedData = $this->objectNormalizer->normalize($fingerprintProps, context: [LiveComponentHydrator::LIVE_CONTEXT => true]);

        return base64_encode(hash_hmac('sha256', serialize($normalizedData), $this->secret, true));
    }
}
