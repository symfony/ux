<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Column;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\DataTable\PropertyReader;

/**
 * @phpstan-type ColumnOptions array{label: string|false|TranslatableInterface|null, property_path: string|false|null, getter: \Closure|null, sort: bool|string, attr: array<string, mixed>, header_attr: array<string, mixed>, ...<string, mixed>}
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class Column
{
    /**
     * @var list<string>
     */
    private readonly array $blockPrefixes;

    /**
     * @param non-empty-list<ColumnTypeInterface> $types   from the root type to the column type
     * @param ColumnOptions                       $options
     */
    public function __construct(
        private readonly string $name,
        private readonly array $types,
        private readonly array $options,
        private readonly PropertyReader $reader = new PropertyReader(),
    ) {
        $this->blockPrefixes = array_map(static fn (ColumnTypeInterface $type): string => $type->getBlockPrefix(), $types);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function isSortable(): bool
    {
        return false !== $this->options['sort'];
    }

    public function getSortPath(): string
    {
        if (\is_string($this->options['sort'])) {
            return $this->options['sort'];
        }

        return str_replace('?', '', \is_string($this->options['property_path']) ? $this->options['property_path'] : $this->name);
    }

    public function getValue(mixed $row): mixed
    {
        if (null !== $this->options['getter']) {
            return ($this->options['getter'])($row);
        }

        $path = $this->options['property_path'] ?? $this->name;

        return \is_string($path) ? $this->reader->read($row, $path) : null;
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function createHeaderView(array $vars = []): ColumnView
    {
        $view = new ColumnView($vars + ['name' => $this->name, 'block_prefixes' => $this->blockPrefixes]);
        foreach ($this->types as $type) {
            $type->buildHeaderView($view, $this, $this->options);
        }

        return $view;
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function createCellView(mixed $row, array $vars = []): ColumnView
    {
        $view = new ColumnView($vars + ['name' => $this->name, 'block_prefixes' => $this->blockPrefixes, 'row' => $row, 'value' => $this->getValue($row)]);
        foreach ($this->types as $type) {
            $type->buildCellView($view, $this, $row, $this->options);
        }

        return $view;
    }
}
