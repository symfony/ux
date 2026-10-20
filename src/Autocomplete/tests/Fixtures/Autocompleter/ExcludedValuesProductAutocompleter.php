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
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;

/**
 * @implements EntityAutocompleterInterface<Product>
 */
#[AutoconfigureTag('ux.entity_autocompleter', ['alias' => 'excluded_values_product'])]
class ExcludedValuesProductAutocompleter implements EntityAutocompleterInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function getEntityClass(): string
    {
        return Product::class;
    }

    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder
    {
        $queryBuilder = $repository->createQueryBuilder('o');

        $excluded = $this->requestStack->getCurrentRequest()?->query->all()['excluded'] ?? [];
        $excluded = \is_array($excluded) ? array_values(array_filter($excluded, 'is_string')) : [];

        if ($excluded = \array_slice($excluded, 0, 500)) {
            $queryBuilder
                ->andWhere('o.id NOT IN (:excluded)')
                ->setParameter('excluded', $excluded)
            ;
        }

        return $queryBuilder;
    }

    public function getLabel(object $entity): string
    {
        return $entity->getName();
    }

    public function getValue(object $entity): string
    {
        return (string) $entity->getId();
    }

    public function getAttributes(object $entity): array
    {
        return [];
    }

    public function getGroupBy(): mixed
    {
        return null;
    }

    public function isGranted(Security $security): bool
    {
        return true;
    }
}
