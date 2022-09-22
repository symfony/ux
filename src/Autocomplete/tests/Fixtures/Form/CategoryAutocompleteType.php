<?php

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\ParentEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CategoryAutocompleteType extends AbstractType
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Category::class,
            'choice_label' => function(Category $category) {
                return '<strong>'.$category->getName().'</strong>';
            },
            'query_builder' => function(EntityRepository $repository) {
                return $repository->createQueryBuilder('category')
                    ->andWhere('category.name LIKE :search')
                    ->setParameter('search', '%foo%');
            },
            'security' => function(Security $security) {
                if ($this->requestStack->getCurrentRequest()?->query->get('enforce_test_security')) {
                    return $security->isGranted('ROLE_USER');
                }

                return true;
            },
            'placeholder' => 'What should we eat?',
            'attr' => [
                'data-controller' => 'custom-autocomplete',
            ],
            'max_results' => 5,
        ]);
    }

    public function getParent(): string
    {
        return ParentEntityAutocompleteType::class;
    }
}
