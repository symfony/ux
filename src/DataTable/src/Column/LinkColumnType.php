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
final class LinkColumnType extends AbstractColumnType
{
    /**
     * The schemes a value is linked to without "href": a stored "javascript:" value must never become a link.
     */
    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'href' => null,
                'link_attr' => [],
            ])
            ->setAllowedTypes('href', ['null', 'string', \Closure::class])
            ->setAllowedTypes('link_attr', 'array')
            ->setInfo('href', 'The URL, or a closure receiving the value and the row and returning it; null to link to the value itself when it is an http(s), mailto or tel URL')
        ;
    }

    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void
    {
        $view->vars['href'] = match (true) {
            $options['href'] instanceof \Closure => ($options['href'])($view->vars['value'], $row),
            null === $options['href'] => self::safeUrl($view->vars['value']),
            default => $options['href'],
        };
        $view->vars['link_attr'] = $options['link_attr'];
    }

    private static function safeUrl(mixed $value): ?string
    {
        if (!\is_string($value) || !preg_match('/^([a-z][a-z0-9+.-]*):/i', trim($value), $matches)) {
            return null;
        }

        return \in_array(strtolower($matches[1]), self::SAFE_SCHEMES, true) ? trim($value) : null;
    }
}
