<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Normalizer;

use App\Model\Address;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AddressNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Address;
    }

    /**
     * @param Address $data
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return [
            'serialized_country' => $data->country,
            'serialized_city' => $data->city,
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Address::class === $type;
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): object
    {
        return Address::create(
            country: $data['serialized_country'],
            city: $data['serialized_city'],
        );
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Address::class => true];
    }
}
