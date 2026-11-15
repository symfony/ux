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

use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Simon Daigre <simon@daig.re>
 */
abstract class AbstractColumnType implements ColumnTypeInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    public function buildHeaderView(ColumnView $view, Column $column, array $options): void
    {
    }

    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
    }

    public function getParent(): ?string
    {
        return ColumnType::class;
    }

    public function getBlockPrefix(): string
    {
        return (string) StringUtil::fqcnToBlockPrefix(static::class);
    }
}
