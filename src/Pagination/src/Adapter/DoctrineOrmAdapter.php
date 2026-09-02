<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Adapter;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Cursor\CursorSlice;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\RuntimeException;
use Symfony\UX\Pagination\Exception\UnsupportedDoctrineQueryException;

/**
 * @internal Built-in adapter. Implement PaginationAdapterInterface for custom sources.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class DoctrineOrmAdapter implements OffsetAdapterInterface, LookaheadAdapterInterface, CursorAdapterInterface
{
    use CursorValuesTrait;

    public function supports(mixed $source): bool
    {
        return $source instanceof QueryBuilder;
    }

    public function slice(mixed $source, int $offset, int $limit): array
    {
        if (!$source instanceof QueryBuilder) {
            throw new InvalidArgumentException('Source must be a Doctrine ORM QueryBuilder.');
        }

        $qb = clone $source;

        $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $this->getPaginatedResults($qb);
    }

    private function assertCountableQuery(QueryBuilder $queryBuilder): void
    {
        if (1 !== \count($queryBuilder->getRootAliases())) {
            throw new UnsupportedDoctrineQueryException('Cannot infer a correct count for a QueryBuilder with multiple root aliases. Use total() to provide the total explicitly.');
        }

        if ([] !== $queryBuilder->getDQLPart('groupBy') || null !== $queryBuilder->getDQLPart('having')) {
            throw new UnsupportedDoctrineQueryException('Cannot infer a correct count for a QueryBuilder using GROUP BY or HAVING. Use total(), lookahead(), or a custom pagination adapter.');
        }
    }

    public function count(mixed $source): int
    {
        if (!$source instanceof QueryBuilder) {
            throw new InvalidArgumentException('Source must be a Doctrine ORM QueryBuilder.');
        }

        $this->assertCountableQuery($source);

        $qb = clone $source;
        $alias = $this->getMainAlias($qb);

        // Reset pagination limits and ordering (ORDER BY is unnecessary for COUNT)
        $qb->setMaxResults(null)
            ->setFirstResult(null)
            ->resetDQLPart('orderBy');

        // Collection-valued and arbitrary joins can duplicate root rows.
        // To-one association joins preserve one row per root entity.
        $countExpr = $this->hasCollectionValuedOrUnknownJoin($qb)
            ? \sprintf('COUNT(DISTINCT %s)', $alias)
            : \sprintf('COUNT(%s)', $alias);

        $qb->select($countExpr);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getCursorContext(mixed $source, ?string $context): string
    {
        if (!$source instanceof QueryBuilder) {
            throw new InvalidArgumentException('Source must be a Doctrine ORM QueryBuilder.');
        }

        $entities = $source->getRootEntities();
        if (1 !== \count($entities)) {
            throw new RuntimeException('Cursor pagination requires exactly one Doctrine root entity.');
        }

        $parameters = [];
        foreach ($source->getParameters() as $parameter) {
            $type = $parameter->getType();
            $parameters[(string) $parameter->getName()] = [
                'type' => $type instanceof \BackedEnum ? $type->value : ($type instanceof \UnitEnum ? $type->name : $type),
                'value' => $this->normalizeCursorContextValue($source, $parameter->getValue()),
            ];
        }
        ksort($parameters);

        return json_encode([
            'adapter' => self::class,
            'entity' => $entities[0],
            'dql' => $source->getDQL(),
            'parameters' => $parameters,
            'context' => $context,
        ], \JSON_THROW_ON_ERROR);
    }

    /**
     * @param string|list<string> $cursorField
     *
     * @return non-empty-list<string>
     */
    public function resolveCursorFields(mixed $source, string|array $cursorField): array
    {
        if (!$source instanceof QueryBuilder) {
            throw new InvalidArgumentException('Source must be a Doctrine ORM QueryBuilder.');
        }
        $this->getMainAlias($source);

        $fields = \is_array($cursorField) ? array_values($cursorField) : [$cursorField];
        if ([] === $fields || array_any($fields, static fn (mixed $field): bool => !\is_string($field) || '' === $field)) {
            throw new InvalidArgumentException('Cursor fields must be non-empty strings.');
        }

        $this->validateFieldNames($source, $fields);

        return $this->ensureDeterministicCursorFields($source, $fields);
    }

    public function resolveCursorOrder(mixed $source, ?array $fields, ?string $direction): CursorOrder
    {
        if (null === $fields || null === $direction) {
            throw new InvalidArgumentException('Doctrine ORM cursor pagination requires an explicit orderBy().');
        }

        return CursorOrder::byFields($this->resolveCursorFields($source, $fields), $direction);
    }

    public function sliceWithLookahead(mixed $source, int $offset, int $limit): array
    {
        if (!$source instanceof QueryBuilder) {
            throw new InvalidArgumentException('Source must be a Doctrine ORM QueryBuilder.');
        }

        $qb = clone $source;

        $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit + 1);

        $items = $this->getPaginatedResults($qb);

        $hasMore = \count($items) > $limit;

        if ($hasMore) {
            array_pop($items);
        }

        return [$items, $hasMore];
    }

    public function sliceWithCursor(
        mixed $source,
        ?CursorBoundary $boundary,
        int $limit,
        CursorOrder $order,
    ): CursorSlice {
        if (!$source instanceof QueryBuilder) {
            throw new InvalidArgumentException('Source must be a Doctrine ORM QueryBuilder.');
        }

        $cursorFields = $order->getFields();
        $direction = $order->getDirection();
        if (null === $cursorFields || null === $direction) {
            throw new InvalidArgumentException('Doctrine ORM cursor pagination requires a field-based cursor order.');
        }

        $qb = clone $source;
        $alias = $this->getMainAlias($qb);

        $fields = array_map(static fn ($field) => $alias.'.'.$field, $cursorFields);
        $this->assertNoExistingCursorOrder($qb);
        $forward = $boundary->forward ?? true;

        if (null !== $boundary) {
            $values = $boundary->values;

            if (\count($values) !== \count($cursorFields)) {
                throw new InvalidArgumentException('Cursor values count does not match cursor fields count.');
            }

            // Forward keeps items after the cursor, backward keeps items before it.
            $after = 'ASC' === $direction ? '>' : '<';
            $operator = $forward ? $after : ('>' === $after ? '<' : '>');
            $parameterPrefix = $this->cursorParameterPrefix(
                $qb,
                \count($fields),
            );

            // Build tuple comparison: (A > x) OR (A = x AND B > y) OR (A = x AND B = y AND C > z)
            $conditions = [];
            for ($i = 0; $i < \count($fields); ++$i) {
                $condition = [];

                for ($j = 0; $j < $i; ++$j) {
                    $condition[] = \sprintf('%s = :%s_%d', $fields[$j], $parameterPrefix, $j);
                }

                $condition[] = \sprintf('%s %s :%s_%d', $fields[$i], $operator, $parameterPrefix, $i);

                $conditions[] = '('.implode(' AND ', $condition).')';
            }

            $qb->andWhere(implode(' OR ', $conditions));

            foreach ($values as $index => $value) {
                [$value, $type] = $this->normalizeDoctrineCursorParameter($qb, $cursorFields[$index], $value);
                $qb->setParameter($parameterPrefix.'_'.$index, $value, $type);
            }
        }

        // Backward navigation queries in reverse order, then restores display order
        $queryDirection = $forward ? $direction : ('ASC' === $direction ? 'DESC' : 'ASC');
        $qb->resetDQLPart('orderBy');
        foreach ($fields as $field) {
            $qb->addOrderBy($field, $queryDirection);
        }

        // Fetch limit + 1 to detect an adjacent page
        $qb->setMaxResults($limit + 1);
        $items = $this->getPaginatedResults($qb);

        $hasExtra = \count($items) > $limit;
        if ($hasExtra) {
            array_pop($items);
        }

        if (!$forward) {
            $items = array_reverse($items);
        }

        $firstCursor = [] !== $items ? $this->extractCursorValues($items[0], $cursorFields) : null;
        $lastCursor = [] !== $items ? $this->extractCursorValues(end($items), $cursorFields) : null;

        if ($forward) {
            // Coming forward: a previous page exists whenever a cursor was used
            $hasNext = $hasExtra;
            $previousCursor = null !== $boundary && null !== $firstCursor ? new CursorBoundary($firstCursor, false) : null;
            $nextCursor = $hasNext && null !== $lastCursor ? new CursorBoundary($lastCursor) : null;
        } else {
            // Coming backward: the page we came from is the next page
            $hasNext = [] !== $items;
            $nextCursor = null !== $lastCursor ? new CursorBoundary($lastCursor) : null;
            $previousCursor = $hasExtra && null !== $firstCursor ? new CursorBoundary($firstCursor, false) : null;
        }

        return new CursorSlice($items, $nextCursor, $previousCursor, $hasNext);
    }

    private function cursorParameterPrefix(QueryBuilder $queryBuilder, int $count): string
    {
        $existing = [];
        foreach ($queryBuilder->getParameters() as $parameter) {
            $existing[(string) $parameter->getName()] = true;
        }
        $suffix = 0;

        do {
            $prefix = 'ux_pagination_cursor'.(0 === $suffix ? '' : '_'.$suffix);
            $available = true;
            for ($index = 0; $index < $count; ++$index) {
                if (isset($existing[$prefix.'_'.$index])) {
                    $available = false;
                    ++$suffix;

                    break;
                }
            }
        } while (!$available);

        return $prefix;
    }

    /**
     * @return array{mixed, string}
     */
    private function normalizeDoctrineCursorParameter(QueryBuilder $queryBuilder, string $field, int|string|float $value): array
    {
        $entity = $queryBuilder->getRootEntities()[0];
        $type = $queryBuilder->getEntityManager()->getClassMetadata($entity)->getTypeOfField($field);
        if (!\in_array($type, [Types::INTEGER, Types::BIGINT, Types::SMALLINT, Types::STRING, Types::GUID, Types::FLOAT, Types::DECIMAL, Types::BOOLEAN, Types::DATETIME_MUTABLE, Types::DATETIME_IMMUTABLE, Types::DATETIMETZ_MUTABLE, Types::DATETIMETZ_IMMUTABLE], true)) {
            throw new InvalidArgumentException(\sprintf('Doctrine type "%s" of cursor field "%s" is not supported.', $type, $field));
        }
        if (\in_array($type, [Types::DATETIME_MUTABLE, Types::DATETIME_IMMUTABLE, Types::DATETIMETZ_MUTABLE, Types::DATETIMETZ_IMMUTABLE], true)) {
            try {
                $value = new \DateTimeImmutable((string) $value);
            } catch (\Exception $exception) {
                throw new InvalidArgumentException(\sprintf('Invalid date cursor value for field "%s".', $field), 0, $exception);
            }
            // Cursor values are normalized to UTC, but Doctrine binds datetime
            // columns as wall time in PHP's default timezone.
            $value = $value->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }

        return [$value, $type];
    }

    private function normalizeCursorContextValue(QueryBuilder $queryBuilder, mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return ['class' => $value::class, 'value' => $value->format('Y-m-d H:i:s.uP')];
        }
        if ($value instanceof \BackedEnum) {
            return ['class' => $value::class, 'value' => $value->value];
        }
        if ($value instanceof \UnitEnum) {
            return ['class' => $value::class, 'name' => $value->name];
        }
        if (\is_object($value)) {
            $metadataFactory = $queryBuilder->getEntityManager()->getMetadataFactory();
            if (!$metadataFactory->isTransient($value::class)) {
                $identifier = $queryBuilder->getEntityManager()->getClassMetadata($value::class)->getIdentifierValues($value);
                if ([] === $identifier) {
                    throw new RuntimeException(\sprintf('Cannot bind a cursor to the unsaved Doctrine object "%s".', $value::class));
                }

                return [
                    'class' => $value::class,
                    'identifier' => $this->normalizeCursorContextValue($queryBuilder, $identifier),
                ];
            }

            throw new RuntimeException(\sprintf('Cannot derive a stable cursor context from Doctrine parameter object "%s". Use a scalar, enum, date, mapped entity, or change the query parameter.', $value::class));
        }
        if (\is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeCursorContextValue($queryBuilder, $item);
            }
            if (!array_is_list($normalized)) {
                ksort($normalized);
            }

            return $normalized;
        }
        if (null === $value || \is_scalar($value)) {
            return $value;
        }

        throw new RuntimeException(\sprintf('Cannot derive a stable cursor context from Doctrine parameter of type "%s".', get_debug_type($value)));
    }

    private function assertNoExistingCursorOrder(QueryBuilder $queryBuilder): void
    {
        $orderParts = $queryBuilder->getDQLPart('orderBy');
        if ([] === $orderParts) {
            return;
        }

        throw new InvalidArgumentException('Doctrine cursor pagination owns ORDER BY. Remove the source QueryBuilder order and configure it with cursor()->orderBy().');
    }

    private function getMainAlias(QueryBuilder $queryBuilder): string
    {
        /** @var array<int, \Doctrine\ORM\Query\Expr\From> $parts */
        $parts = $queryBuilder->getDQLPart('from');

        if (empty($parts)) {
            throw new RuntimeException('QueryBuilder has no FROM clause.');
        }

        $from = reset($parts);
        \assert($from instanceof \Doctrine\ORM\Query\Expr\From);

        $alias = $from->getAlias();

        return '' !== $alias ? $alias : 'entity';
    }

    /**
     * @return list<mixed>
     */
    private function getPaginatedResults(QueryBuilder $queryBuilder): array
    {
        // Only joins that may duplicate root rows need Doctrine's collection
        // paginator. Association joins to one entity preserve the LIMIT/OFFSET
        // semantics and can execute as one plain query.
        if (!$this->hasCollectionValuedOrUnknownJoin($queryBuilder)) {
            /** @var array<mixed> $rows */
            $rows = $queryBuilder->getQuery()->getResult();

            return array_values($rows);
        }

        $paginator = new DoctrinePaginator($queryBuilder->getQuery(), fetchJoinCollection: true);

        /** @var list<mixed> $results */
        $results = array_values(iterator_to_array($paginator));

        return $results;
    }

    /**
     * Detect whether a join can produce multiple SQL rows for one root entity.
     *
     * Association joins are resolved through Doctrine metadata, including
     * chained joins. Arbitrary range joins and joins that cannot be resolved
     * stay on the conservative collection-paginator path.
     */
    private function hasCollectionValuedOrUnknownJoin(QueryBuilder $queryBuilder): bool
    {
        /** @var array<string, list<Join>> $joinParts */
        $joinParts = $queryBuilder->getDQLPart('join');
        if ([] === $joinParts) {
            return false;
        }

        $aliases = [];
        foreach ($queryBuilder->getRootAliases() as $index => $alias) {
            $entity = $queryBuilder->getRootEntities()[$index] ?? null;
            if (\is_string($entity)) {
                $aliases[$alias] = $entity;
            }
        }

        $pending = [];
        foreach ($joinParts as $joins) {
            foreach ($joins as $join) {
                $pending[] = $join;
            }
        }

        while ([] !== $pending) {
            $remaining = [];
            $resolved = 0;

            foreach ($pending as $join) {
                if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)$/D', $join->getJoin(), $matches)) {
                    return true;
                }

                [, $sourceAlias, $association] = $matches;
                if (!isset($aliases[$sourceAlias])) {
                    $remaining[] = $join;
                    continue;
                }

                $metadata = $queryBuilder->getEntityManager()->getClassMetadata($aliases[$sourceAlias]);
                if (!$metadata->hasAssociation($association)) {
                    return true;
                }
                if ($metadata->isCollectionValuedAssociation($association)) {
                    return true;
                }

                $targetAlias = $join->getAlias();
                if (null === $targetAlias || '' === $targetAlias) {
                    return true;
                }

                $aliases[$targetAlias] = $metadata->getAssociationTargetClass($association);
                ++$resolved;
            }

            if (0 === $resolved) {
                return true;
            }

            $pending = $remaining;
        }

        return false;
    }

    /**
     * Validate that all cursor field names are real entity fields
     * to prevent DQL injection via user-controlled sort parameters.
     *
     * @param list<string> $fields
     */
    private function validateFieldNames(QueryBuilder $queryBuilder, array $fields): void
    {
        $entityClass = $queryBuilder->getRootEntities()[0];

        $metadata = $queryBuilder->getEntityManager()->getClassMetadata($entityClass);
        $validNames = $metadata->getFieldNames();

        foreach ($fields as $field) {
            if (!\in_array($field, $validNames, true)) {
                throw new InvalidArgumentException(\sprintf('Invalid cursor field "%s" for entity "%s". Cursor fields must be mapped scalar fields, not associations.', $field, $entityClass));
            }

            $mapping = $metadata->getFieldMapping($field);
            if ($mapping->nullable) {
                throw new InvalidArgumentException(\sprintf('Cursor field "%s" must be non-nullable.', $field));
            }
        }
    }

    /**
     * @param non-empty-list<string> $cursorFields
     *
     * @return non-empty-list<string>
     */
    private function ensureDeterministicCursorFields(QueryBuilder $queryBuilder, array $cursorFields): array
    {
        $identifiers = $this->getIdentifierFieldNames($queryBuilder);
        $lowercaseCursorFields = array_map('strtolower', $cursorFields);

        foreach ($identifiers as $identifier) {
            if (\in_array(strtolower($identifier), $lowercaseCursorFields, true)) {
                continue;
            }

            $cursorFields[] = $identifier;
        }

        return $cursorFields;
    }

    /**
     * @return list<string>
     */
    private function getIdentifierFieldNames(QueryBuilder $queryBuilder): array
    {
        $entityClass = $queryBuilder->getRootEntities()[0];

        $identifiers = $queryBuilder
            ->getEntityManager()
            ->getClassMetadata($entityClass)
            ->getIdentifierFieldNames();

        if ([] === $identifiers) {
            throw new RuntimeException(\sprintf('Entity "%s" has no identifier field.', $entityClass));
        }

        return array_values($identifiers);
    }
}
