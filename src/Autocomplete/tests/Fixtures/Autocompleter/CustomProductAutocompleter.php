<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;

class CustomProductAutocompleter implements EntityAutocompleterInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntitySearchUtil $entitySearchUtil,
    ) {
    }

    public function getEntityClass(): string
    {
        return Product::class;
    }

    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder
    {
        $queryBuilder = $repository->createQueryBuilder('p')
            ->andWhere('p.isEnabled = :enabled')
            ->setParameter('enabled', true);

        $this->entitySearchUtil->addSearchClause(
            $queryBuilder,
            $query,
            $this->getEntityClass(),
            ['name', 'description']
        );

        return $queryBuilder;
    }

    public function getLabel(object $entity): string
    {
        return $entity->getName();
    }

    public function getValue(object $entity): mixed
    {
        return $entity->getId();
    }

    public function isGranted(Security $security): bool
    {
        if ($this->requestStack->getCurrentRequest()?->query->get('enforce_test_security')) {
            return $security->isGranted('ROLE_USER');
        }

        return true;
    }

    public function getGroupBy(): mixed
    {
        return null;
    }
}
