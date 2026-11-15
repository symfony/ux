<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Integration;

use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\DataTable\Exception\LogicException;
use Symfony\UX\DataTable\Tests\Fixtures\ProductsTableComponent;
use Symfony\UX\DataTable\Tests\Fixtures\TestKernel;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

final class ComponentWithDataTableTraitTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testRendersTheDataTableWithLiveAttributes(): void
    {
        $crawler = $this->createLiveComponent('products_table')->render()->crawler();

        self::assertSame(['Armchair', 'Chair'], self::names($crawler));
        self::assertSame('sortBy', $crawler->filter('th a')->first()->attr('data-live-action-param'));
        self::assertSame('name', $crawler->filter('th a')->first()->attr('data-live-column-param'));
        self::assertSame('*', $crawler->filter('form')->attr('data-model'));
        self::assertCount(0, $crawler->filter('form input[type=hidden]'));
        self::assertCount(0, $crawler->filter('form a'));
        self::assertSame('goToPage', $crawler->filter('nav.ux-pagination a')->last()->attr('data-live-action-param'));
        self::assertSame('2', $crawler->filter('nav.ux-pagination a')->last()->attr('data-live-page-param'));
    }

    public function testSortsPaginatesFiltersAndResets(): void
    {
        $component = $this->createLiveComponent('products_table');

        $component->call('sortBy', ['column' => 'name']);
        self::assertSame(['name' => 'desc'], $component->component()->sort);
        self::assertSame(['Table', 'Lamp'], self::names($component->render()->crawler()));

        $component->call('sortBy', ['column' => 'price']);
        self::assertSame(['price' => 'asc'], $component->component()->sort);

        $component->call('goToPage', ['page' => 2]);
        self::assertSame(['Armchair', 'Table'], self::names($component->render()->crawler()));

        $component->call('goToPage', ['page' => -3]);
        self::assertSame(1, $component->component()->page);

        $component->call('goToPage', ['page' => 2]);
        $component->set('filter.q', 'chair');
        self::assertSame(1, $component->component()->page);
        $crawler = $component->render()->crawler();
        self::assertSame(['Chair', 'Armchair'], self::names($crawler));
        self::assertSame('resetFilters', $crawler->filter('form a')->attr('data-live-action-param'));

        $component->call('resetFilters');
        self::assertSame(['q' => '', 'active' => '', 'cheap' => null], $component->component()->filter);
        self::assertSame(['Chair', 'Lamp'], self::names($component->render()->crawler()));
    }

    public function testInitializesTheFiltersAndCapturesTheRoute(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/live');
        self::assertResponseIsSuccessful();

        $props = json_decode((string) $crawler->filter('[data-live-props-value]')->attr('data-live-props-value'), true);
        self::assertSame(['q' => '', 'active' => '', 'cheap' => null], $props['filter']);
        self::assertSame('live', $props['dataTableRoute']);
        self::assertSame('/live?sort%5Bname%5D=desc', $crawler->filter('th a')->first()->attr('href'));
    }

    public function testFiltersWithACheckbox(): void
    {
        $component = $this->createLiveComponent('products_table');
        self::assertNull($component->component()->filter['cheap']);
        self::assertCount(0, $component->render()->crawler()->filter('form a'));

        $component->set('filter.cheap', '1');
        $crawler = $component->render()->crawler();
        self::assertSame(['Chair', 'Lamp'], self::names($crawler));
        self::assertSame('checked', $crawler->filter('input[name="filter[cheap]"]')->attr('checked'));
        self::assertCount(0, $crawler->filter('nav.ux-pagination'));
    }

    public function testClearsAFilterWithADefault(): void
    {
        $component = $this->createLiveComponent('products_table', ['cheapByDefault' => true]);
        self::assertSame('1', $component->component()->filter['cheap']);
        self::assertSame(['Chair', 'Lamp'], self::names($component->render()->crawler()));

        $component->set('filter.cheap', null);
        $crawler = $component->render()->crawler();
        self::assertSame(['Armchair', 'Chair'], self::names($crawler));
        self::assertNull($crawler->filter('input[name="filter[cheap]"]')->attr('checked'));
        self::assertCount(0, $crawler->filter('form a'));
    }

    public function testKeepsThePageOfADataTableWithoutFilters(): void
    {
        $component = $this->createLiveComponent('products_table', ['filterable' => false, 'page' => 2]);
        $component->refresh();

        self::assertSame(2, $component->component()->page);
        self::assertSame(['Lamp', 'Table'], self::names($component->render()->crawler()));
    }

    public function testDoesNotCaptureTheRouteOfALiveRequest(): void
    {
        self::bootKernel();
        $request = new Request(attributes: ['_route' => 'admin_live', '_route_params' => [], '_live_component' => 'products_table']);
        self::getContainer()->get('request_stack')->push($request);

        self::assertNull($this->createLiveComponent('products_table')->component()->dataTableRoute);
    }

    #[TestWith(['page_parameter', 'page'])]
    #[TestWith(['sort_parameter', 'sort'])]
    #[TestWith(['filter_parameter', 'filter'])]
    public function testRequiresTheDefaultParameters(string $option, string $prop): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(\sprintf('The "%s" option of the data table "product_data_table" must be "%s" to bind it to the "%2$s" live prop.', $option, $prop));

        /** @var ProductsTableComponent $component */
        $component = $this->createLiveComponent('products_table', ['options' => [$option => 'p']])->component();
        $component->getDataTable();
    }

    /**
     * @return list<string>
     */
    private static function names(Crawler $crawler): array
    {
        return $crawler->filter('tbody tr td:first-child')->each(static fn (Crawler $td) => $td->text());
    }
}
