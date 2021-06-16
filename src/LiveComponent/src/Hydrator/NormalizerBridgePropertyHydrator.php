<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Hydrator;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Exception\UnsupportedHydrationException;
use Symfony\UX\LiveComponent\PropertyHydratorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class NormalizerBridgePropertyHydrator implements PropertyHydratorInterface
{
    /** @var NormalizerInterface|DenormalizerInterface */
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('Normalizer must also be a Denormalizer.');
        }

        $this->normalizer = $normalizer;
    }

    public function dehydrate($value)
    {
        if (!$this->normalizer->supportsNormalization($value)) {
            throw new UnsupportedHydrationException();
        }

        return $this->normalizer->normalize($value);
    }

    public function hydrate(string $type, $value)
    {
        if (!$this->normalizer->supportsDenormalization($value, $type)) {
            throw new UnsupportedHydrationException();
        }

        return $this->normalizer->denormalize($value, $type);
    }
}
