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

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\UX\DataTable\Query\Operator;

/**
 * The root filter type, keeping the rows equal to the submitted value.
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class FilterType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'label' => null,
                'form_type' => TextType::class,
                'form_options' => [],
                'query_path' => null,
                'operator' => Operator::Equals,
                'apply' => null,
            ])
            ->setAllowedTypes('label', ['null', 'bool', 'string', TranslatableInterface::class])
            ->setAllowedTypes('form_type', 'string')
            ->setAllowedTypes('form_options', 'array')
            ->setAllowedTypes('query_path', ['null', 'string'])
            ->setAllowedTypes('operator', Operator::class)
            ->setAllowedTypes('apply', ['null', \Closure::class])
            ->setNormalizer('form_options', static fn (Options $options, array $formOptions): array => $formOptions + [
                'label' => $options['label'],
                'required' => false,
            ])
            ->setInfo('query_path', 'The path filtered in the query; null to use the filter name')
            ->setInfo('apply', 'A closure receiving the query, the submitted data and the filter, applying the filter instead of the operator')
        ;
    }

    public function getParent(): ?string
    {
        return null;
    }
}
