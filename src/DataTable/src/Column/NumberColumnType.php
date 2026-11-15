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
final class NumberColumnType extends AbstractColumnType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'decimals' => null,
                'decimal_separator' => '.',
                'thousands_separator' => ',',
            ])
            ->setAllowedTypes('decimals', ['null', 'int'])
            ->setInfo('decimals', 'The number of decimals; null to keep those of the value')
            ->setAllowedTypes('decimal_separator', 'string')
            ->setAllowedTypes('thousands_separator', 'string')
        ;
    }

    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
        /** @var array{decimals: int|null, decimal_separator: string, thousands_separator: string, ...<string, mixed>} $options */
        $value = $view->vars['value'];
        if (!is_numeric($value)) {
            return;
        }

        $view->vars['value'] = number_format((float) $value, $options['decimals'] ?? self::countDecimals($value), $options['decimal_separator'], $options['thousands_separator']);
    }

    /**
     * The decimals of a numeric string as given (e.g. a DECIMAL column), or of a
     * float written with at most 10 decimals, never in scientific notation.
     */
    private static function countDecimals(int|float|string $value): int
    {
        if (\is_string($value) && !preg_match('/[eE]/', $value)) {
            $fraction = strrchr($value, '.');
        } else {
            $fraction = strrchr(rtrim(\sprintf('%.10F', (float) $value), '0'), '.');
        }

        return false !== $fraction ? \strlen($fraction) - 1 : 0;
    }
}
