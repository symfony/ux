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

use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\Cursor\CursorSlice;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\RuntimeException;

/**
 * @internal Built-in adapter. Implement PaginationAdapterInterface for custom sources.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class DoctrineDbalAdapter implements OffsetAdapterInterface, LookaheadAdapterInterface, CursorAdapterInterface
{
    use CursorValuesTrait;

    public function supports(mixed $source): bool
    {
        return $source instanceof QueryBuilder;
    }

    public function slice(mixed $source, int $offset, int $limit): array
    {
        $queryBuilder = $this->requireQueryBuilder($source);

        return $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function count(mixed $source): int
    {
        $queryBuilder = $this->requireQueryBuilder($source);
        $queryBuilder->setFirstResult(0)->setMaxResults(null);
        $this->resetOrderBy($queryBuilder);

        $countQuery = $queryBuilder
            ->sub()
            ->select('COUNT(*)')
            ->from('('.$queryBuilder->getSQL().')', '_ux_pagination_count')
            ->setParameters($queryBuilder->getParameters(), $queryBuilder->getParameterTypes());

        $count = $countQuery->executeQuery()->fetchOne();
        if (!\is_int($count) && !(\is_string($count) && ctype_digit($count))) {
            throw new RuntimeException(\sprintf('Doctrine DBAL count query must return a non-negative integer, got "%s".', get_debug_type($count)));
        }

        return (int) $count;
    }

    public function getCursorContext(mixed $source, ?string $context): string
    {
        $queryBuilder = $this->requireQueryBuilder($source);
        $parameters = [];
        $types = $queryBuilder->getParameterTypes();
        foreach ($queryBuilder->getParameters() as $name => $value) {
            $type = $types[$name] ?? null;
            $parameters[(string) $name] = [
                'type' => $type instanceof \BackedEnum ? $type->value : ($type instanceof \UnitEnum ? $type->name : $type),
                'value' => $this->normalizeCursorContextValue($value),
            ];
        }
        ksort($parameters);

        return json_encode([
            'adapter' => self::class,
            'sql' => $queryBuilder->getSQL(),
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
        $this->requireQueryBuilder($source);

        $fields = \is_array($cursorField) ? array_values($cursorField) : [$cursorField];
        if ([] === $fields) {
            throw new InvalidArgumentException('At least one cursor field is required.');
        }
        foreach ($fields as $field) {
            if (!\is_string($field)) {
                throw new InvalidArgumentException('DBAL cursor fields must be non-empty strings.');
            }
            if (!preg_match('/^(?:[a-zA-Z_][a-zA-Z0-9_]*\.)?[a-zA-Z_][a-zA-Z0-9_]*$/D', $field)) {
                throw new InvalidArgumentException(\sprintf('Invalid DBAL cursor field "%s". Use a column name or a qualified column name.', $field));
            }
        }

        return $fields;
    }

    public function resolveCursorOrder(mixed $source, ?array $fields, ?string $direction): CursorOrder
    {
        if (null === $fields || null === $direction) {
            throw new InvalidArgumentException('Doctrine DBAL cursor pagination requires an explicit orderBy().');
        }

        return CursorOrder::byFields($this->resolveCursorFields($source, $fields), $direction);
    }

    public function sliceWithLookahead(mixed $source, int $offset, int $limit): array
    {
        $queryBuilder = $this->requireQueryBuilder($source);
        $items = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit + 1)
            ->executeQuery()
            ->fetchAllAssociative();

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
        $queryBuilder = $this->requireQueryBuilder($source);
        $fields = $order->getFields();
        $direction = $order->getDirection();
        if (null === $fields || null === $direction) {
            throw new InvalidArgumentException('Doctrine DBAL cursor pagination requires a field-based cursor order.');
        }

        if ($this->hasOrderBy($queryBuilder)) {
            throw new InvalidArgumentException('Doctrine DBAL cursor pagination owns ORDER BY. Remove the source QueryBuilder order and configure it with cursor()->orderBy().');
        }

        $forward = $boundary->forward ?? true;
        if (null !== $boundary) {
            if (\count($boundary->values) !== \count($fields)) {
                throw new InvalidArgumentException('Cursor values count does not match cursor fields count.');
            }

            $after = 'ASC' === $direction ? '>' : '<';
            $operator = $forward ? $after : ('>' === $after ? '<' : '>');
            $conditions = [];
            $parameters = [];
            $parameterPrefix = $this->cursorParameterPrefix(
                $queryBuilder,
                \count($fields),
            );

            foreach ($fields as $index => $field) {
                $parts = [];
                for ($previous = 0; $previous < $index; ++$previous) {
                    $parts[] = \sprintf('%s = :%s', $fields[$previous], $this->cursorParameterName($parameterPrefix, $previous));
                }
                $parts[] = \sprintf('%s %s :%s', $field, $operator, $this->cursorParameterName($parameterPrefix, $index));
                $conditions[] = '('.implode(' AND ', $parts).')';
                $parameters[$this->cursorParameterName($parameterPrefix, $index)] = $boundary->values[$index];
            }

            $queryBuilder->andWhere(implode(' OR ', $conditions));
            foreach ($parameters as $name => $value) {
                $queryBuilder->setParameter($name, $value);
            }
        }

        $queryDirection = $forward ? $direction : ('ASC' === $direction ? 'DESC' : 'ASC');
        foreach ($fields as $field) {
            $queryBuilder->addOrderBy($field, $queryDirection);
        }

        $items = $queryBuilder
            ->setFirstResult(0)
            ->setMaxResults($limit + 1)
            ->executeQuery()
            ->fetchAllAssociative();

        $hasExtra = \count($items) > $limit;
        if ($hasExtra) {
            array_pop($items);
        }
        if (!$forward) {
            $items = array_reverse($items);
        }

        $resultFields = array_map(static fn (string $field): string => str_contains($field, '.') ? substr($field, strrpos($field, '.') + 1) : $field, $fields);
        $this->assertUniqueTuples(array_map(fn (array $item) => $this->extractCursorValues($item, $resultFields), $items));
        $firstCursor = [] !== $items ? $this->extractCursorValues($items[0], $resultFields) : null;
        $lastCursor = [] !== $items ? $this->extractCursorValues($items[array_key_last($items)], $resultFields) : null;

        if ($forward) {
            $hasNext = $hasExtra;
            $previousCursor = null !== $boundary && null !== $firstCursor ? new CursorBoundary($firstCursor, false) : null;
            $nextCursor = $hasNext && null !== $lastCursor ? new CursorBoundary($lastCursor) : null;
        } else {
            $hasNext = [] !== $items;
            $nextCursor = null !== $lastCursor ? new CursorBoundary($lastCursor) : null;
            $previousCursor = $hasExtra && null !== $firstCursor ? new CursorBoundary($firstCursor, false) : null;
        }

        return new CursorSlice($items, $nextCursor, $previousCursor, $hasNext);
    }

    private function requireQueryBuilder(mixed $source): QueryBuilder
    {
        if (!$source instanceof QueryBuilder) {
            throw new InvalidArgumentException('Source must be a Doctrine DBAL QueryBuilder.');
        }

        return clone $source;
    }

    private function resetOrderBy(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->resetOrderBy();
    }

    private function hasOrderBy(QueryBuilder $queryBuilder): bool
    {
        $withoutOrder = clone $queryBuilder;
        $withoutOrder->resetOrderBy();

        return $queryBuilder->getSQL() !== $withoutOrder->getSQL();
    }

    private function cursorParameterPrefix(QueryBuilder $queryBuilder, int $count): string
    {
        $existing = array_fill_keys(
            array_map('strval', array_keys($queryBuilder->getParameters())),
            true,
        );
        $suffix = 0;

        do {
            $prefix = 'ux_pagination_cursor'.(0 === $suffix ? '' : '_'.$suffix);
            $available = true;
            for ($index = 0; $index < $count; ++$index) {
                if (isset($existing[$this->cursorParameterName($prefix, $index)])) {
                    $available = false;
                    ++$suffix;

                    break;
                }
            }
        } while (!$available);

        return $prefix;
    }

    private function cursorParameterName(string $prefix, int $index): string
    {
        return $prefix.'_'.$index;
    }

    private function normalizeCursorContextValue(mixed $value): mixed
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
        if (\is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeCursorContextValue($item);
            }
            if (!array_is_list($normalized)) {
                ksort($normalized);
            }

            return $normalized;
        }
        if (null === $value || \is_scalar($value)) {
            return $value;
        }

        throw new RuntimeException(\sprintf('Cannot derive a stable cursor context from Doctrine DBAL parameter of type "%s". Use a scalar, enum, date, or array value.', get_debug_type($value)));
    }
}
