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
use Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter\CustomProductAutocompleter;
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
        $this->assertCount(3, $executor->fetchResults($autocompleter, ''));
    }
}
