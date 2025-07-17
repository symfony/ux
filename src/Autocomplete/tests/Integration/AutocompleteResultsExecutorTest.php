<?php

declare(strict_types=1);

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
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter\CustomAttributesProductAutocompleter;
use Symfony\UX\Autocomplete\Tests\Fixtures\Factory\ProductFactory;
use Symfony\UX\Autocomplete\Tests\Fixtures\Kernel;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AutocompleteResultsExecutorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testItReturnsExtraAttributes(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->disableForms();
        $kernel->boot();

        $product = ProductFactory::createOne(['name' => 'Foo']);

        /** @var AutocompleteResultsExecutor $executor */
        $executor = $kernel->getContainer()->get('public.results_executor');
        $autocompleter = $kernel->getContainer()->get(CustomAttributesProductAutocompleter::class);
        $data = $executor->fetchResults($autocompleter, '', 1);
        $this->assertCount(1, $data->results);
        $this->assertSame(['disabled' => true, 'value' => $product->getId(), 'text' => 'Foo'], $data->results[0]);
    }
}
