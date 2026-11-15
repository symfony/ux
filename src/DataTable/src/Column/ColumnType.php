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

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\DataTable\Humanizer;

/**
 * The root column type, rendering the value as is.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class ColumnType extends AbstractColumnType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'label' => null,
                'property_path' => null,
                'getter' => null,
                'sort' => false,
                'attr' => [],
                'header_attr' => [],
            ])
            ->setAllowedTypes('label', ['null', 'bool', 'string', TranslatableInterface::class])
            ->setAllowedValues('label', static fn (mixed $label): bool => true !== $label)
            ->setAllowedTypes('property_path', ['null', 'bool', 'string'])
            ->setAllowedValues('property_path', static fn (mixed $path): bool => true !== $path)
            ->setAllowedTypes('getter', ['null', \Closure::class])
            ->setAllowedTypes('sort', ['bool', 'string'])
            ->setAllowedTypes('attr', 'array')
            ->setAllowedTypes('header_attr', 'array')
            ->setInfo('label', 'The header label; null to humanize the column name, false to hide it')
            ->setInfo('property_path', 'The path of the value in the row; null to use the column name, false for no value')
            ->setInfo('getter', 'A closure receiving the row and returning the value, instead of the property path')
            ->setInfo('sort', 'Whether the column is sortable, or the path to sort it by')
        ;
    }

    public function buildHeaderView(ColumnView $view, Column $column, array $options): void
    {
        $view->vars['label'] = $options['label'] ?? Humanizer::humanize($column->getName());
        $view->vars['translatable_label'] = $view->vars['label'] instanceof TranslatableInterface;
        $view->vars['attr'] = $options['header_attr'];
    }

    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
        $view->vars['attr'] = $options['attr'];
    }

    public function getParent(): ?string
    {
        return null;
    }
}
