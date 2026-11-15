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
 * @author Simon Daigre <simon@daig.re>
 */
final class DateTimeColumnType extends AbstractColumnType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'format' => 'Y-m-d H:i:s',
                'timezone' => null,
            ])
            ->setAllowedTypes('format', 'string')
            ->setAllowedTypes('timezone', ['null', 'string'])
        ;
    }

    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
        /** @var array{format: string, timezone: string|null, ...<string, mixed>} $options */
        $value = $view->vars['value'];
        if (!$value instanceof \DateTimeInterface) {
            return;
        }

        if (null !== $options['timezone']) {
            $value = \DateTimeImmutable::createFromInterface($value)->setTimezone(new \DateTimeZone($options['timezone']));
        }

        $view->vars['value'] = $value->format($options['format']);
    }
}
