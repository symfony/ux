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

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Category;

#[AsEntityAutocompleteField]
class CategoryWithFilterQueryAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Category::class,
            'filter_query' => static function (QueryBuilder $qb, string $query) {
                if (!$query) {
                    return;
                }

                $qb->andWhere('entity.name LIKE :filter')
                    ->setParameter('filter', '%'.$query.'%');
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
