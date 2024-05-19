<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
class DoctrineIdAccessor
{
    private $doctrine;

    public function __construct(?ManagerRegistry $doctrine = null)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @return array<string, array<string, string>>|array<string, string>|null
     */
    public function getEntityId(object $entity, ?ObjectManager $em = null): ?array
    {
        $em = $em ?? $this->doctrine?->getManagerForClass($entity::class);

        if ($em) {
            return $this->getIdentifierValues($em, $entity);
        }

        return null;
    }

    /**
     * @return array<string, string>|array<string, array<string, string>>
     */
    private function getIdentifierValues(ObjectManager $em, object $entity): array
    {
        $values = $em->getClassMetadata($entity::class)->getIdentifierValues($entity);

        foreach ($values as $key => $value) {
            if (\is_object($value)) {
                $values[$key] = $this->getIdentifierValues($em, $value);
            }
        }

        return $values;
    }
}
