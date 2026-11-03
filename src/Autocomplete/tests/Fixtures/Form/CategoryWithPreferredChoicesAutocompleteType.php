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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Category;

#[AsEntityAutocompleteField]
class CategoryWithPreferredChoicesAutocompleteType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Category::class,
            'choice_label' => 'name',
            'query_builder' => static function (EntityRepository $repository) {
                return $repository->createQueryBuilder('category')
                    ->orderBy('category.name', 'ASC');
            },
            'preferred_choices' => function (Options $options) {
                return $this->entityManager->getRepository(Category::class)->findBy(['code' => 'preferred'], ['name' => 'DESC']);
            },
            'max_results' => 2,
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
