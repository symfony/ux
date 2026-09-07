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
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\CategoryFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\CategoryTagFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\ProductFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\ProductTagFactory;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class FieldAutocompleterTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testItReturnsBasicResults(): void
    {
        $category = CategoryFactory::createOne(['name' => 'foo and baz']);
        CategoryFactory::createOne(['name' => 'foo and bar']);

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/category_autocomplete_type')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 2)
            ->assertJsonMatches('results[0].value', (string) $category->getId())
            ->assertJsonMatches('results[0].text', '<strong>foo and baz</strong>')
            ->get('/test/autocomplete/category_autocomplete_type?query=bar')
            ->assertJsonMatches('length(results)', 1)
        ;
    }

    public function testItUsesTheCustomQuery(): void
    {
        CategoryFactory::createOne(['name' => 'foo and bar']);
        CategoryFactory::createOne(['name' => 'baz and bar']);

        $this->browser()
            ->throwExceptions()
            // query already ONLY returns items matching "foo"
            ->get('/test/autocomplete/category_autocomplete_type?query=bar')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 1)
            ->assertJsonMatches('results[0].text', '<strong>foo and bar</strong>')
        ;
    }

    public function testItEnforcesSecurity(): void
    {
        CategoryFactory::createMany(3, [
            'name' => 'foo so that it matches custom query',
        ]);

        $this->browser()
            // enforce_test_security is a custom flag used in FieldAutocompleterTest
            ->get('/test/autocomplete/category_autocomplete_type?enforce_test_security=1')
            ->assertStatus(401)
            ->actingAs(new InMemoryUser('mr_autocompleter', null, ['ROLE_USER']))
            ->get('/test/autocomplete/category_autocomplete_type?enforce_test_security=1', [
                'server' => [
                    'PHP_AUTH_USER' => 'mr_autocompleter',
                    'PHP_AUTH_PW' => 'symfonypass',
                ],
            ])
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 3)
        ;
    }

    public function testItCheckMaxResultsOption(): void
    {
        CategoryFactory::createMany(30, ['name' => 'foo']);

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/category_autocomplete_type?query=foo')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 25)
        ;
    }

    public function testItWorksWithoutAChoiceLabel(): void
    {
        CategoryFactory::createMany(5, ['name' => 'foo']);

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/category_no_choice_label_autocomplete_type?query=foo')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 5)
        ;
    }

    public function testItUsesTheCustomStringValue(): void
    {
        $category = CategoryFactory::createOne(['code' => 'foo']);

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/category_with_property_name_as_custom_value?query=foo')
            ->assertSuccessful()
            ->assertJsonMatches('results[0].value', 'foo')
            ->assertJsonMatches('results[0].text', $category->getName())
        ;
    }

    public function testItUsesTheCustomCallbackValue(): void
    {
        $category = CategoryFactory::createOne(['code' => 'foo']);

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/category_with_callback_as_custom_value?query=foo')
            ->assertSuccessful()
            ->assertJsonMatches('results[0].value', 'foo')
            ->assertJsonMatches('results[0].text', $category->getName())
        ;
    }

    public function testItSearchesByTags(): void
    {
        $productTag = ProductTagFactory::createOne(['name' => 'technology']);
        $categoryTag = CategoryTagFactory::createOne(['name' => 'home appliances']);
        $category = CategoryFactory::createOne(['name' => 'Electronics', 'tags' => [$categoryTag]]);
        $product1 = ProductFactory::createOne(['name' => 'Smartphone', 'tags' => [$productTag], 'category' => $category]);
        $product2 = ProductFactory::createOne(['name' => 'Laptop', 'category' => $category]);
        ProductFactory::createOne(['name' => 'Microwave']);

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/product_with_tags_autocomplete_type?query=technology')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 1)
            ->assertJsonMatches('results[0].value', (string) $product1->getId())
            ->assertJsonMatches('results[0].text', '<strong>Smartphone</strong>')
        ;

        $this->browser()
            ->get('/test/autocomplete/product_with_tags_autocomplete_type?query=home appliance')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 2)
            ->assertJsonMatches('results[0].value', (string) $product1->getId())
            ->assertJsonMatches('results[1].value', (string) $product2->getId())
        ;
    }
}
