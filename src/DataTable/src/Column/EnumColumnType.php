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

/**
 * Renders a translatable enum with its translation, any other enum with its name.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class EnumColumnType extends AbstractColumnType
{
    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
        $view->vars['translatable'] = $view->vars['value'] instanceof TranslatableInterface;
        if ($view->vars['value'] instanceof \UnitEnum && !$view->vars['translatable']) {
            $view->vars['value'] = $view->vars['value']->name;
        }
    }
}
