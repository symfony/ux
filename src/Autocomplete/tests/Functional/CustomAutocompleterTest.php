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
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\ProductFactory;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

// tests CustomProductAutocompleter
class CustomAutocompleterTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testItReturnsBasicResults(): void
    {
        $product = ProductFactory::createOne(['name' => 'foo']);
        ProductFactory::createOne(['name' => 'bar']);
        ProductFactory::createOne(['name' => 'foo and bar']);

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/custom_product')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 3)
            ->assertJsonMatches('results[0].value', $product->getId())
            ->assertJsonMatches('results[0].text', 'foo')
            ->get('/test/autocomplete/custom_product?query=bar')
            ->assertJsonMatches('length(results)', 2)
        ;
    }

    public function testItUsesTheCustomQuery(): void
    {
        ProductFactory::createOne(['name' => 'foo']);
        ProductFactory::new(['name' => 'foo and bar'])
            ->disable()
            ->create();

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/custom_product?query=foo')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 1)
            ->assertJsonMatches('results[0].text', 'foo')
        ;
    }

    public function testItOnlySearchedOnSearchableFields(): void
    {
        ProductFactory::createOne(['name' => 'foo', 'price' => 50]);
        ProductFactory::createOne(['name' => 'bar', 'description' => 'foo 50', 'price' => 55]);

        $this->browser()
            ->throwExceptions()
            // search on name or description
            ->get('/test/autocomplete/custom_product?query=foo')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 2)
            ->get('/test/autocomplete/custom_product?query=50')
            // price should not be searched
            ->assertJsonMatches('length(results)', 1)
            ->assertJsonMatches('results[0].text', 'bar')
        ;
    }

    public function testItEnforcesSecurity(): void
    {
        ProductFactory::createMany(3);

        $this->browser()
            // enforce_test_security is a custom flag used in CustomProductAutocomplete
            ->get('/test/autocomplete/custom_product?enforce_test_security=1')
            ->assertStatus(401)
            ->actingAs(new InMemoryUser('mr_autocompleter', null, ['ROLE_USER']))
            ->get('/test/autocomplete/custom_product?enforce_test_security=1', [
                'server' => [
                    'PHP_AUTH_USER' => 'mr_autocompleter',
                    'PHP_AUTH_PW' => 'symfonypass',
                ],
            ])
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 3)
        ;
    }

    public function testItReturns404OnBadAlias(): void
    {
        $this->browser()
            ->get('/test/autocomplete/not_real')
            ->assertStatus(404)
        ;
    }

    public function testItWorksWithCustomRoute(): void
    {
        $product = ProductFactory::createOne(['name' => 'foo']);
        ProductFactory::createOne(['name' => 'bar']);
        ProductFactory::createOne(['name' => 'foo and bar']);

        $this->browser()
            ->throwExceptions()
            ->get('/alt/test/autocomplete/custom_product')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 3)
            ->assertJsonMatches('results[0].value', $product->getId())
            ->assertJsonMatches('results[0].text', 'foo')
            ->get('/test/autocomplete/custom_product?query=bar')
            ->assertJsonMatches('length(results)', 2)
        ;
    }
}
