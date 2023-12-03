<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\CategoryFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\IngredientFactory;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

// tests CategoryAutocompleteType
class AutocompleteFormRenderingTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testFieldsRenderWithStimulusController()
    {
        $this->browser()
            ->throwExceptions()
            ->get('/test-form')
            ->assertElementAttributeContains('#product_category', 'data-controller', 'custom-autocomplete symfony--ux-autocomplete--autocomplete')
            ->assertElementAttributeContains('#product_category', 'data-symfony--ux-autocomplete--autocomplete-url-value', '/test/autocomplete/category_autocomplete_type?extra_options=')
            ->assertElementAttributeContains('#product_category', 'data-symfony--ux-autocomplete--autocomplete-min-characters-value', '2')
            ->assertElementAttributeContains('#product_category', 'data-symfony--ux-autocomplete--autocomplete-max-results-value', '25')

            ->assertElementAttributeContains('#product_portionSize', 'data-controller', 'symfony--ux-autocomplete--autocomplete')
            ->assertElementAttributeContains('#product_tags', 'data-controller', 'symfony--ux-autocomplete--autocomplete')
            ->assertElementAttributeContains('#product_tags', 'data-symfony--ux-autocomplete--autocomplete-tom-select-options-value', 'createOnBlur')
        ;
    }

    public function testCategoryFieldSubmitsCorrectly()
    {
        $firstCat = CategoryFactory::createOne(['name' => 'First cat']);
        CategoryFactory::createOne(['name' => 'in space']);
        CategoryFactory::createOne(['name' => 'ate pizza']);
        $fooCat = CategoryFactory::createOne(['name' => 'I contain "foo" which CategoryAutocompleteType uses its query_builder option.']);

        $this->browser()
            ->throwExceptions()
            ->get('/test-form')
            // the field renders empty (but the placeholder is there)
            ->assertElementCount('#product_category option', 1)
            ->assertNotContains('First cat')

            ->post('/test-form', [
                'body' => [
                    'product' => ['category' => (string) $firstCat->getId()],
                ],
            ])
            // the option does NOT match something returned by query_builder
            // so ONLY the placeholder shows up
            ->assertElementCount('#product_category option', 1)
            ->assertNotContains('First cat')
            ->post('/test-form', [
                'body' => [
                    'product' => ['category' => (string) $fooCat->getId()],
                ],
            ])
            // the one option + placeholder now shows up
            ->assertElementCount('#product_category option', 2)
            ->assertContains('which CategoryAutocompleteType uses')
        ;
    }

    public function testProperlyLoadsChoicesWithIdValueObjects()
    {
        $ingredient1 = IngredientFactory::createOne(['name' => 'Flour']);
        $ingredient2 = IngredientFactory::createOne(['name' => 'Sugar']);

        $this->browser()
            ->throwExceptions()
            ->get('/test-form')
            ->assertElementCount('#product_ingredients option', 0)
            ->assertNotContains('Flour')
            ->assertNotContains('Sugar')
            ->post('/test-form', [
                'body' => [
                    'product' => [
                        'ingredients' => [
                            (string) $ingredient1->getId(),
                            (string) $ingredient2->getId(),
                        ],
                    ],
                ],
            ])
            // assert that selected options are not lost
            ->assertElementCount('#product_ingredients option', 2)
            ->assertContains('Flour')
            ->assertContains('Sugar')
        ;
    }

    public function testMultipleDoesNotFailWithoutSelectedChoices()
    {
        $this->browser()
            ->throwExceptions()
            ->get('/test-form')
            ->assertElementCount('#product_ingredients option', 0)
            ->assertNotContains('Flour')
            ->assertNotContains('Sugar')
            ->post('/test-form', [
                'body' => [
                    'product' => [
                        'ingredients' => [
                            'autocomplete' => [],
                        ],
                    ],
                ],
            ])
            ->assertElementCount('#product_ingredients option', 0)
        ;
    }

    public function testItUsesPassedExtraOptions()
    {
        $ingredient1 = IngredientFactory::createOne(['name' => 'Flour']);
        $ingredient2 = IngredientFactory::createOne(['name' => 'Sugar']);
        $ingredient3 = IngredientFactory::createOne(['name' => 'Modified Flour']);

        $this->browser()
            ->throwExceptions()
            ->get('/test-form')
            ->assertElementCount('#product_ingredients option', 0)
            ->assertNotContains('Flour')
            ->assertNotContains('Sugar')
            ->assertNotContains('Modified Flour')
            // request all three ingredients
            ->post('/test-form', [
                'body' => [
                    'product' => [
                        'ingredients' => [
                            (string) $ingredient1->getId(),
                            (string) $ingredient2->getId(),
                            (string) $ingredient3->getId(),
                        ],
                    ],
                ],
            ])
            // assert that "Modified Flour" is not included
            ->assertElementCount('#product_ingredients option', 2)
            ->assertContains('Flour')
            ->assertContains('Sugar')
            ->assertNotContains('Modified Flour')
        ;
    }

    public function testItReturnsErrorWhenSendingMalformedExtraOptions(): void
    {
        $extraOptionsWithoutChecksum = $this->encodeData(['foo' => 'bar']);
        $extraOptionsWithInvalidChecksum = $this->encodeData(['foo' => 'bar', '@checksum' => 'invalid']);
        $extraOptionsWithValidChecksum = $this->encodeData(['foo' => 'bar', '@checksum' => 'O2nYjcGr/l8GmUuYUSfE52hoyEL0NtDhBzUbn17KVHQ=']);

        $this->browser()
            ->post(sprintf('/test/autocomplete/category_autocomplete_type?extra_options=%s', $extraOptionsWithoutChecksum))
            ->assertStatus(400)
            ->post(sprintf('/test/autocomplete/category_autocomplete_type?extra_options=%s', $extraOptionsWithInvalidChecksum))
            ->assertStatus(400)
            ->post(sprintf('/test/autocomplete/category_autocomplete_type?extra_options=%s', $extraOptionsWithValidChecksum))
            ->assertStatus(200)
        ;
    }

    private function encodeData(array $data): string
    {
        return base64_encode(json_encode($data));
    }
}
