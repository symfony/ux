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

use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\DataTable\Query\QueryInterface;

/**
 * Keeps the rows where one of the "fields" contains the submitted term.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class SearchFilterType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('fields')
            ->setDefaults([
                'form_type' => SearchType::class,
                'apply' => static function (QueryInterface $query, string $term, Filter $filter): void {
                    /** @var non-empty-array<string> $fields the "fields" option */
                    $fields = $filter->getOptions()['fields'];
                    $query->search(array_values($fields), $term);
                },
            ])
            ->setAllowedTypes('fields', 'string[]')
            ->setAllowedValues('fields', static fn (array $fields): bool => [] !== $fields)
        ;
    }
}
