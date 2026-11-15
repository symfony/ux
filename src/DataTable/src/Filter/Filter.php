<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Filter;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\DataTable\Query\Operator;
use Symfony\UX\DataTable\Query\QueryInterface;

/**
 * @phpstan-type FilterOptions array{label: string|bool|TranslatableInterface|null, form_type: class-string<FormTypeInterface>, form_options: array<string, mixed>, query_path: string|null, operator: Operator, apply: \Closure|null, ...<string, mixed>}
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class Filter
{
    /**
     * @param FilterOptions $options
     */
    public function __construct(
        private readonly string $name,
        private readonly array $options,
    ) {
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

    /**
     * The path filtered in the query: the "query_path" option, or the filter name.
     */
    public function getQueryPath(): string
    {
        return $this->options['query_path'] ?? $this->name;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder->add($this->name, $this->options['form_type'], $this->options['form_options']);
    }

    /**
     * Applies the submitted data to the query, unless it is empty or an unchecked checkbox.
     */
    public function apply(QueryInterface $query, mixed $data): void
    {
        if (null === $data || '' === $data || (is_countable($data) && 0 === \count($data))) {
            return;
        }

        // An unchecked checkbox means "no filter", unlike the "No" choice of a boolean filter
        if (false === $data && is_a($this->options['form_type'], CheckboxType::class, true)) {
            return;
        }

        if (null !== $this->options['apply']) {
            ($this->options['apply'])($query, $data, $this);

            return;
        }

        $query->filter($this->getQueryPath(), $this->options['operator'], $data);
    }
}
