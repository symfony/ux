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

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity2;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Entity2Normalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        [, $id] = \explode(':', $data);

        return $this->doctrine->getRepository(Entity2::class)->find($id);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return Entity2::class === $type;
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        return 'entity2:'.$object->id;
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $data instanceof Entity2;
    }
}
