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

/**
 * @author Simon Daigre <simon@daig.re>
 */
final class BooleanFilterType extends AbstractFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('form_type', ChoiceType::class)
            ->addNormalizer('form_options', static fn (Options $options, array $formOptions): array => $formOptions + [
                'choices' => ['Yes' => true, 'No' => false],
                'choice_translation_domain' => 'UXDataTableBundle',
            ])
        ;
    }
}
