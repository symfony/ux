<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Query;

use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\DataTable\Exception\InvalidArgumentException;

/**
 * Sorts and filters a Doctrine ORM query builder.
 *
 * A path starts with an alias of the query ("c.name"), or is relative to the
 * root entity ("name", "price.amount" for an embeddable); its to-one
 * associations ("category.parent.name") are left joined. To-many associations
 * would duplicate the rows: join them in the query builder and use their alias.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class DoctrineOrmQuery implements QueryInterface
{
    private readonly QueryBuilder $queryBuilder;

    /**
     * @var array<string, \SortDirection>
     */
    private array $sort = [];

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
    }

    public function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
    }

    public function sort(string $path, \SortDirection $direction): void
    {
        $this->sort[$this->resolvePath($path)] = $direction;
    }

    public function filter(string $path, Operator $operator, mixed $value): void
    {
        $field = $this->resolvePath($path);
        $parameter = $this->createParameter(match ($operator) {
            Operator::Contains => '%'.self::escape($value).'%',
            Operator::StartsWith => self::escape($value).'%',
            Operator::EndsWith => '%'.self::escape($value),
            Operator::In => is_iterable($value) ? [...$value] : [$value],
            default => $value,
        });

        $this->queryBuilder->andWhere(match ($operator) {
            Operator::Equals => "$field = $parameter",
            Operator::NotEquals => "$field <> $parameter",
            Operator::Contains, Operator::StartsWith, Operator::EndsWith => "LOWER($field) LIKE $parameter ESCAPE '!'",
            Operator::GreaterThan => "$field > $parameter",
            Operator::GreaterThanOrEqual => "$field >= $parameter",
            Operator::LessThan => "$field < $parameter",
            Operator::LessThanOrEqual => "$field <= $parameter",
            Operator::In => "$field IN ($parameter)",
        });
    }

    public function search(array $paths, string $term): void
    {
        $parameter = $this->createParameter('%'.self::escape($term).'%');
        $this->queryBuilder->andWhere($this->queryBuilder->expr()->orX(
            ...array_map(fn (string $path): string => "LOWER({$this->resolvePath($path)}) LIKE $parameter ESCAPE '!'", $paths),
        ));
    }

    public function getSource(): QueryBuilder
    {
        $queryBuilder = clone $this->queryBuilder;
        if ([] !== $this->sort) {
            // The data table sort comes first, the initial order breaks the ties
            /** @var list<OrderBy> $orderBy */
            $orderBy = $queryBuilder->getDQLPart('orderBy');
            $queryBuilder->resetDQLPart('orderBy');
            foreach ($this->sort as $path => $direction) {
                $queryBuilder->addOrderBy($path, \SortDirection::Ascending === $direction ? 'ASC' : 'DESC');
            }
            foreach ($orderBy as $order) {
                $queryBuilder->addOrderBy($order);
            }
        }

        return $queryBuilder;
    }

    private function resolvePath(string $path): string
    {
        $parts = explode('.', $path);
        if (\count($parts) > 1 && \in_array($parts[0], $this->queryBuilder->getAllAliases(), true)) {
            return $path;
        }

        $entityManager = $this->queryBuilder->getEntityManager();
        $metadata = $entityManager->getClassMetadata($this->queryBuilder->getRootEntities()[0]);
        $alias = $this->queryBuilder->getRootAliases()[0];
        while (\count($parts) > 1 && $metadata->hasAssociation($parts[0])) {
            $association = array_shift($parts);
            if (!$metadata->isSingleValuedAssociation($association)) {
                throw new InvalidArgumentException(\sprintf('The path "%s" goes through the to-many association "%s", which would duplicate the rows: join it in the query builder and use its alias.', $path, $association));
            }

            $joinAlias = $alias.'_'.$association;
            if (!\in_array($joinAlias, $this->queryBuilder->getAllAliases(), true)) {
                $this->queryBuilder->leftJoin($alias.'.'.$association, $joinAlias);
            }

            $alias = $joinAlias;
            $metadata = $entityManager->getClassMetadata($metadata->getAssociationTargetClass($association));
        }

        return $alias.'.'.implode('.', $parts);
    }

    private function createParameter(mixed $value): string
    {
        $name = 'ux_data_table_'.$this->queryBuilder->getParameters()->count();
        $this->queryBuilder->setParameter($name, $value);

        return ':'.$name;
    }

    private static function escape(mixed $value): string
    {
        return mb_strtolower(strtr(\is_scalar($value) || $value instanceof \Stringable ? (string) $value : '', ['!' => '!!', '%' => '!%', '_' => '!_']));
    }
}
