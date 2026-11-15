<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\DataTable\Column\AbstractColumnType;
use Symfony\UX\DataTable\Column\Column;
use Symfony\UX\DataTable\Column\ColumnView;
use Symfony\UX\DataTable\Column\NumberColumnType;

/**
 * A column type with a dependency, fetched from the container.
 */
final class CurrencyColumnType extends AbstractColumnType
{
    public function __construct(
        #[Autowire('EUR')]
        private readonly string $currency,
    ) {
    }

    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
        $view->vars['value'] .= ' '.$this->currency;
    }

    public function getParent(): string
    {
        return NumberColumnType::class;
    }
}
