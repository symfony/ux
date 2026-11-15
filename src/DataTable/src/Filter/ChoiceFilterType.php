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

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\DataTable\Query\Operator;

/**
 * Keeps the rows equal to the chosen value, or to one of the chosen values when "multiple".
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class ChoiceFilterType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'form_type' => ChoiceType::class,
                'choices' => null,
                'operator' => static fn (Options $options): Operator => \is_array($formOptions = $options['form_options']) && ($formOptions['multiple'] ?? false) ? Operator::In : Operator::Equals,
            ])
            ->setAllowedTypes('choices', ['null', 'array'])
            // An EntityType or an EnumType "form_type" loads its own choices
            ->addNormalizer('form_options', static fn (Options $options, array $formOptions): array => null === $options['choices'] ? $formOptions : $formOptions + ['choices' => $options['choices']])
        ;
    }
}
