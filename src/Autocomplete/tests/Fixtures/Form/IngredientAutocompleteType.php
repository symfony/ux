<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Ingredient;

#[AsEntityAutocompleteField]
class IngredientAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Ingredient::class,
            'choice_label' => function (Ingredient $ingredient) {
                return '<strong>'.$ingredient->getName().'</strong>';
            },
            'multiple' => true,
            'query_builder' => function (Options $options): callable {
                return function (EntityRepository $repository) use ($options) {
                    $qb = $repository->createQueryBuilder('o');

                    if ($options['extra_options']['banned_ingredient'] ?? false) {
                        $qb->andWhere('o.name != :banned_ingredient')
                            ->setParameter('banned_ingredient', $options['extra_options']['banned_ingredient']);
                    }

                    return $qb;
                };
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
