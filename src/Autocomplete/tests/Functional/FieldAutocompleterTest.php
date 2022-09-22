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
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

// tests CategoryAutocompleteType
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

    public function testItCheckMaxResultsOption() : void
    {
        CategoryFactory::createMany(30, ['name' => 'foo']);

        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/category_autocomplete_type?query=foo')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 5)
        ;
    }
}
