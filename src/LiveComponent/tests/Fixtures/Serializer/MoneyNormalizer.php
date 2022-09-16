<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Money;

final class MoneyNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        return new Money(...\explode('|', $data));
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return Money::class === $type;
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        return \implode('|', [$object->amount, $object->currency]);
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $data instanceof Money;
    }
}
