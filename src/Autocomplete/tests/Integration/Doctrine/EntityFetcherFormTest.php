<?php

namespace Symfony\UX\Autocomplete\Tests\Integration\Doctrine;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\Autocomplete\EntityFetcherInterface;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Category;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\CategoryFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Form\CategoryAutocompleteType;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EntityFetcherFormTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testItSuccessfullyCallEntityFetcher(): void
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $category1 = CategoryFactory::createOne(['name' => 'foods']);

        $entityFetcher = $this->createMock(EntityFetcherInterface::class);
        $entityFetcher->expects($this->once())
            ->method('fetchSingleEntity')
            ->with(Category::class, $category1->getId())
            ->willReturn($category1->object());

        $form = $formFactory->create(CategoryAutocompleteType::class, options: [
            'entity_fetcher' => $entityFetcher,
        ]);

        $form->submit([
            'autocomplete' => $category1->getId(),
        ]);
    }
}
