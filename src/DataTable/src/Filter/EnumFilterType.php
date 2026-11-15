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

use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\DataTable\Query\Operator;

/**
 * @author Simon Daigre <simon@daig.re>
 */
final class EnumFilterType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('class')
            ->setDefaults([
                'form_type' => EnumType::class,
                'operator' => static fn (Options $options): Operator => \is_array($formOptions = $options['form_options']) && ($formOptions['multiple'] ?? false) ? Operator::In : Operator::Equals,
            ])
            ->setAllowedTypes('class', 'string')
            ->addNormalizer('form_options', static fn (Options $options, array $formOptions): array => $formOptions + ['class' => $options['class']])
        ;
    }
}
