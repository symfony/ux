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

/**
 * Renders the cell with a Twig template, receiving the "value" and the "row".
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class TemplateColumnType extends AbstractColumnType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('template')
            ->setDefault('template_vars', [])
            ->setAllowedTypes('template', 'string')
            ->setAllowedTypes('template_vars', ['array', \Closure::class])
            ->setInfo('template_vars', 'Extra variables, or a closure receiving the value and the row and returning them')
        ;
    }

    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
        $view->vars['template'] = $options['template'];
        $view->vars['template_vars'] = $options['template_vars'] instanceof \Closure ? ($options['template_vars'])($view->vars['value'], $row) : $options['template_vars'];
    }
}
