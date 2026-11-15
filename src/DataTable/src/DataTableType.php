<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The root data table type.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class DataTableType extends AbstractDataTableType
{
    public function __construct(
        private readonly string $theme = '@UXDataTable/theme/default.html.twig',
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'theme' => $this->theme,
                'attr' => [],
                'translation_domain' => null,
                'page_parameter' => 'page',
                'sort_parameter' => 'sort',
                'filter_parameter' => 'filter',
                'per_page' => null,
                'lookahead' => false,
                'turbo_frame' => false,
                'batch_identifier' => 'id',
                'batch_parameter' => 'ids',
            ])
            ->setAllowedTypes('theme', 'string')
            ->setAllowedTypes('attr', 'array')
            ->setAllowedTypes('translation_domain', ['null', 'bool', 'string'])
            ->setAllowedTypes('page_parameter', 'string')
            ->setAllowedTypes('sort_parameter', 'string')
            ->setAllowedTypes('filter_parameter', 'string')
            ->setAllowedTypes('per_page', ['null', 'int'])
            ->setAllowedValues('per_page', static fn (?int $perPage): bool => null === $perPage || $perPage > 0)
            ->setAllowedTypes('lookahead', 'bool')
            ->setAllowedTypes('turbo_frame', 'bool')
            ->setAllowedTypes('batch_identifier', 'string')
            ->setAllowedTypes('batch_parameter', 'string')
            ->setInfo('per_page', 'The number of rows per page; null to use the symfony/ux-pagination default')
            ->setInfo('lookahead', 'Whether to paginate without counting the rows, showing only previous and next links')
            ->setInfo('batch_identifier', 'The path of the row identifier sent by the batch actions')
            ->setInfo('batch_parameter', 'The parameter holding the identifiers of the rows checked for a batch action')
            ->setInfo('turbo_frame', 'Whether to render the data table in a Turbo Frame, to sort, filter and paginate without reloading the page')
        ;
    }

    public function getParent(): ?string
    {
        return null;
    }
}
