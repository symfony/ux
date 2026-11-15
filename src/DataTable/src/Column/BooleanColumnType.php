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

/**
 * @author Simon Daigre <simon@daig.re>
 */
final class BooleanColumnType extends AbstractColumnType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'true_label' => null,
                'false_label' => null,
            ])
            ->setAllowedTypes('true_label', ['null', 'string', TranslatableInterface::class])
            ->setAllowedTypes('false_label', ['null', 'string', TranslatableInterface::class])
            ->setInfo('true_label', 'The label of true, translated in the domain of the data table; null for a translated "Yes"')
            ->setInfo('false_label', 'The label of false, translated in the domain of the data table; null for a translated "No"')
        ;
    }

    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
        if (null === $view->vars['value']) {
            return;
        }

        $label = $view->vars['value'] ? $options['true_label'] : $options['false_label'];
        if (null === $label) {
            $label = $view->vars['value'] ? 'Yes' : 'No';
            $view->vars['translation_domain'] = 'UXDataTableBundle';
        }

        $view->vars['value'] = $label;
        $view->vars['translatable'] = $label instanceof TranslatableInterface;
    }
}
