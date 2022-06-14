<?php

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;

class CustomProductAutocompleter implements EntityAutocompleterInterface
{
    public function __construct(
        private RequestStack $requestStack
    )
    {
    }

    public function getEntityClass(): string
    {
        return Product::class;
    }

    public function getQueryBuilder(EntityRepository $repository): QueryBuilder
    {
        return $repository->createQueryBuilder('p')
            ->andWhere('p.isEnabled = :enabled')
            ->setParameter('enabled', true);
    }

    public function getLabel(object $entity): string
    {
        return $entity->getName();
    }

    public function getValue(object $entity): mixed
    {
        return $entity->getId();
    }

    public function getSearchableFields(): ?array
    {
        return ['name', 'description'];
    }

    public function isGranted(Security $security): bool
    {
        if ($this->requestStack->getCurrentRequest()?->query->get('enforce_test_security')) {
            return $security->isGranted('ROLE_USER');
        }

        return true;
    }
}
