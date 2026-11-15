<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\DataTable\Exception\InvalidArgumentException;
use Symfony\UX\DataTable\Query\ArrayQuery;
use Symfony\UX\DataTable\Query\DoctrineOrmQuery;
use Symfony\UX\DataTable\Query\QueryInterface;
use Symfony\UX\Pagination\PaginatorInterface;

/**
 * @phpstan-import-type DataTableOptions from DataTable
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class DataTableFactory
{
    private readonly PropertyReader $reader;

    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly FormFactoryInterface $formFactory,
        private readonly TypeRegistry $registry = new TypeRegistry(),
        private readonly ?UrlGeneratorInterface $urlGenerator = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
    ) {
        $this->reader = new PropertyReader($propertyAccessor);
    }

    /**
     * @param class-string<DataTableTypeInterface> $type
     * @param mixed                                $source  an iterable, a Doctrine ORM query builder or a QueryInterface
     * @param array<string, mixed>                 $options
     * @param string|null                          $name    defaults to the block prefix of the type
     */
    public function create(string $type, mixed $source, array $options = [], ?string $name = null): DataTable
    {
        return $this->createBuilder($type, $source, $options, $name)->getDataTable();
    }

    /**
     * @param class-string<DataTableTypeInterface> $type
     * @param array<string, mixed>                 $options
     */
    public function createBuilder(string $type, mixed $source, array $options = [], ?string $name = null): DataTableBuilder
    {
        [$types, $resolved] = $this->registry->resolve($type, DataTableTypeInterface::class, $options);
        /** @var DataTableOptions $options the types allowed by the resolver */
        $options = $resolved;
        $builder = new DataTableBuilder($name ?? $types[array_key_last($types)]->getBlockPrefix(), $types, $options, $this->createQuery($source), $this->registry, $this->formFactory, $this->paginator, $this->urlGenerator, $this->reader);

        foreach ($types as $dataTableType) {
            $dataTableType->buildDataTable($builder, $options);
        }

        return $builder;
    }

    private function createQuery(mixed $source): QueryInterface
    {
        return match (true) {
            $source instanceof QueryInterface => $source,
            $source instanceof QueryBuilder => new DoctrineOrmQuery($source),
            is_iterable($source) => new ArrayQuery($source, $this->reader),
            default => throw new InvalidArgumentException(\sprintf('A data table source must be an iterable, a Doctrine ORM query builder or a "%s", "%s" given.', QueryInterface::class, get_debug_type($source))),
        };
    }
}
