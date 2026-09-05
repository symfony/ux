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
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DirectAutocompleterTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testItReturnsBasicResults()
    {
        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/in_memory_colors')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 5)
            ->assertJsonMatches('results[0].value', 'red')
            ->assertJsonMatches('results[0].text', 'Red')
        ;
    }

    public function testItFiltersResults()
    {
        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/in_memory_colors?query=re')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 2)
            ->assertJsonMatches('results[0].value', 'red')
            ->assertJsonMatches('results[0].text', 'Red')
            ->assertJsonMatches('results[1].value', 'green')
            ->assertJsonMatches('results[1].text', 'Green')
        ;
    }

    public function testItPaginatesResults()
    {
        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/in_memory_colors')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 5)
            ->get('/test/autocomplete/in_memory_colors?page=2')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 5)
            ->get('/test/autocomplete/in_memory_colors?page=3')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 2)
            ->assertJsonMatches('next_page', null)
        ;
    }

    public function testItReturnsEmptyResultsForNoMatch()
    {
        $this->browser()
            ->throwExceptions()
            ->get('/test/autocomplete/in_memory_colors?query=nonexistent')
            ->assertSuccessful()
            ->assertJsonMatches('length(results)', 0)
            ->assertJsonMatches('next_page', null)
        ;
    }
}
