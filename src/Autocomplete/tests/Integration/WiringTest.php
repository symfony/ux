<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter\CustomGroupByProductAutocompleter;
use Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter\CustomProductAutocompleter;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\CategoryFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\ProductFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Kernel;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class WiringTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = new Kernel('test', true);
        $kernel->disableForms();

        return $kernel;
    }

    public function testWiringWithoutForm(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->disableForms();
        $kernel->boot();

        ProductFactory::createMany(3);

        /** @var AutocompleteResultsExecutor $executor */
        $executor = $kernel->getContainer()->get('public.results_executor');
        $autocompleter = $kernel->getContainer()->get(CustomProductAutocompleter::class);
        $data = $executor->fetchResults($autocompleter, '', 1);
        $this->assertCount(3, $data->results);
        $this->assertFalse($data->hasNextPage);
    }

    public function testWiringWithManyResults(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->disableForms();
        $kernel->boot();

        ProductFactory::createMany(22);

        /** @var AutocompleteResultsExecutor $executor */
        $executor = $kernel->getContainer()->get('public.results_executor');
        $autocompleter = $kernel->getContainer()->get(CustomProductAutocompleter::class);
        $data = $executor->fetchResults($autocompleter, '', 1);
        $this->assertCount(10, $data->results);
        $this->assertTrue($data->hasNextPage);
        $data = $executor->fetchResults($autocompleter, '', 2);
        $this->assertCount(10, $data->results);
        $this->assertTrue($data->hasNextPage);
        $data = $executor->fetchResults($autocompleter, '', 3);
        $this->assertCount(2, $data->results);
        $this->assertFalse($data->hasNextPage);
        $data = $executor->fetchResults($autocompleter, '', 4);
        $this->assertCount(0, $data->results);
        $this->assertFalse($data->hasNextPage);
    }

    public function testWiringWithoutFormAndGroupByOption(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->disableForms();
        $kernel->boot();

        $category1 = CategoryFactory::createOne(['name' => 'foods']);
        $category2 = CategoryFactory::createOne(['name' => 'toys']);
        ProductFactory::createOne(['name' => 'pizza', 'category' => $category1]);
        ProductFactory::createOne(['name' => 'toy food', 'category' => $category2]);
        ProductFactory::createOne(['name' => 'puzzle', 'category' => $category2]);

        /** @var AutocompleteResultsExecutor $executor */
        $executor = $kernel->getContainer()->get('public.results_executor');
        $autocompleter = $kernel->getContainer()->get(CustomGroupByProductAutocompleter::class);
        $data = $executor->fetchResults($autocompleter, '', 1);
        $this->assertCount(3, $data->results);
        $this->assertCount(2, $data->optgroups);
    }
}
