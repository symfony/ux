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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\UX\DataTable\Action\DropdownActionType;
use Symfony\UX\DataTable\Column\LinkColumnType;
use Symfony\UX\DataTable\DataTableFactory;
use Symfony\UX\DataTable\DataTableType;
use Symfony\UX\DataTable\Tests\Fixtures\TestKernel;
use Twig\Environment;

final class DataTableIntegrationTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testRendersTheDataTable(): void
    {
        $crawler = self::request('/en/products?sort[total]=desc&filter[q]=a&tab=all');

        self::assertSame(['Name', 'Price', 'Active', 'Status', 'Category', 'Show', '', 'Total ▼'], $crawler->filter('thead th')->each(static fn (Crawler $th) => $th->text()));
        self::assertSame([
            ['Table', '1,250.50', 'No', 'Online', 'Furniture', 'Show', 'TABLE', '1,250.50 EUR'],
            ['Armchair', '30.00', 'Yes', 'Online', '', 'Show', 'ARMCHAIR', '30.00 EUR'],
        ], $crawler->filter('tbody tr')->each(static fn (Crawler $tr) => $tr->filter('td')->each(static fn (Crawler $td) => $td->text())));

        $sorted = $crawler->filter('th.ux-data-table__sorted');
        self::assertSame('ux-data-table__sorted amount', $sorted->attr('class'));
        self::assertSame('descending', $sorted->attr('aria-sort'));
        self::assertSame('/en/products?sort%5Btotal%5D=asc&filter%5Bq%5D=a&tab=all', $sorted->filter('a')->attr('href'));
        self::assertSame('/en/products?sort%5Bname%5D=asc&filter%5Bq%5D=a&tab=all', $crawler->filter('th')->first()->filter('a')->attr('href'));
        self::assertCount(0, $crawler->filter('th')->eq(2)->filter('a'));
        self::assertSame('/products/Table', $crawler->filter('tbody a')->attr('href'));
        self::assertCount(2, $crawler->filter('span.badge-blue'));

        $form = $crawler->filter('form.ux-data-table__filters');
        self::assertSame('get', $form->attr('method'));
        self::assertSame('/en/products', $form->attr('action'));
        self::assertSame(['sort[total]' => 'desc', 'tab' => 'all'], array_combine(
            $form->filter('input[type=hidden]')->each(static fn (Crawler $input) => $input->attr('name')),
            $form->filter('input[type=hidden]')->each(static fn (Crawler $input) => $input->attr('value')),
        ));
        self::assertSame('a', $form->filter('input[name="filter[q]"]')->attr('value'));
        self::assertCount(0, $form->filter('input[name*=_token]'));
        self::assertSame('/en/products?sort%5Btotal%5D=desc&tab=all', $form->filter('a')->attr('href'));

        self::assertSame('/en/products?sort%5Btotal%5D=desc&filter%5Bq%5D=a&tab=all&page=2', $crawler->filter('nav.ux-pagination a')->last()->attr('href'));
        self::assertCount(0, $crawler->filter('turbo-frame'));
    }

    public function testRendersTheActions(): void
    {
        $crawler = self::request('/en/products?actions=1');

        self::assertSame('/products/new', $crawler->filter('.ux-data-table__toolbar a')->attr('href'));
        self::assertSame('Create', $crawler->filter('.ux-data-table__toolbar a')->text());
        self::assertSame(['', 'Name ▲', 'Price', 'Active', 'Status', 'Category', 'Show', '', 'Total', ''], $crawler->filter('thead th')->each(static fn (Crawler $th) => trim($th->text())));

        $row = $crawler->filter('tbody tr')->first();
        $checkbox = $row->filter('input[type=checkbox]');
        self::assertSame(['product_data_table-batch', 'ids[]', 'Armchair'], [$checkbox->attr('form'), $checkbox->attr('name'), $checkbox->attr('value')]);

        $actions = $row->filter('td.ux-data-table__row-actions');
        self::assertSame('/products/Armchair/edit', $actions->filter('a')->attr('href'));

        $open = $actions->filter('button[command=show-modal]');
        self::assertSame(['Delete', 'product_data_table-0-delete-confirmation'], [$open->text(), $open->attr('commandfor')]);
        $dialog = $actions->filter('dialog#product_data_table-0-delete-confirmation');
        self::assertSame('Are you sure?', $dialog->filter('p')->text());
        self::assertSame(['post', '/products/Armchair'], [$dialog->filter('form')->attr('method'), $dialog->filter('form')->attr('action')]);
        self::assertSame('DELETE', $dialog->filter('input[name=_method]')->attr('value'));
        self::assertNotEmpty($dialog->filter('input[name=_token]')->attr('value'));
        self::assertSame('Cancel', $dialog->filter('button[command=close]')->text());

        $dropdown = $actions->filter('details.ux-data-table__dropdown');
        self::assertSame('More', $dropdown->filter('summary')->text());
        self::assertSame('/products/archive', $dropdown->filter('li form')->attr('action'));
        self::assertCount(0, $dropdown->filter('li input[name=_method]'));

        $form = $crawler->filter('form#product_data_table-batch');
        self::assertSame('post', $form->attr('method'));
        $export = $crawler->filter('dialog#product_data_table-batch-export-confirmation button[type=submit]');
        self::assertSame(['product_data_table-batch', '/products/export', 'post', '_token'], [$export->attr('form'), $export->attr('formaction'), $export->attr('formmethod'), $export->attr('name')]);
        self::assertNotEmpty($export->attr('value'));
        self::assertSame('Export the products?', $crawler->filter('dialog#product_data_table-batch-export-confirmation p')->text());

        // Without rows, no row has actions
        $crawler = self::request('/en/products?actions=1&filter[q]=nothing');
        self::assertSame('9', $crawler->filter('tbody td')->attr('colspan'));
    }

    public function testRendersTheDefaultSortWithoutFilters(): void
    {
        $crawler = self::request('/en/products');

        self::assertSame(['Armchair', 'Chair'], $crawler->filter('tbody tr td:first-child')->each(static fn (Crawler $td) => $td->text()));
        self::assertCount(0, $crawler->filter('form a'));
    }

    public function testRendersTheEmptyDataTableInATurboFrame(): void
    {
        $crawler = self::request('/en/products?turbo=1&filter[q]=nothing');

        self::assertSame('product_data_table', $crawler->filter('turbo-frame')->attr('id'));
        self::assertSame('advance', $crawler->filter('turbo-frame')->attr('data-turbo-action'));
        self::assertSame('No results.', $crawler->filter('tbody td')->text());
        self::assertSame('8', $crawler->filter('tbody td')->attr('colspan'));
    }

    #[TestWith(['test'])]
    #[TestWith(['tabler'])]
    public function testNavigatesTheTurboFrameOnlyWithItsOwnLinks(string $environment): void
    {
        $crawler = self::request('/en/products?turbo=1&actions=1&filter[q]=a', $environment);

        self::assertSame('_top', $crawler->filter('turbo-frame')->attr('target'));
        $frameLinks = $crawler->filter('th a, nav.ux-pagination a, ul.pagination a, form.ux-data-table__filters, form.ux-data-table__filters a');
        self::assertCount(8, $frameLinks);
        self::assertSame(array_fill(0, 8, 'product_data_table'), $frameLinks->each(static fn (Crawler $link) => $link->attr('data-turbo-frame')));
        self::assertCount(0, $crawler->filter('.ux-data-table__toolbar [data-turbo-frame], tbody [data-turbo-frame]'));
    }

    public function testTranslatesTheDataTable(): void
    {
        $crawler = self::request('/fr/products?filter[q]=chair');

        self::assertSame('Oui', $crawler->filter('tbody td')->eq(2)->text());
        self::assertSame('Filtrer', $crawler->filter('form button')->text());
        self::assertSame('Réinitialiser', $crawler->filter('form a')->text());
        self::assertSame(['', 'Oui', 'Non'], $crawler->filter('select[name="filter[active]"] option')->each(static fn (Crawler $option) => $option->text()));
    }

    public function testDoesNotTranslateWithoutTranslationDomain(): void
    {
        $crawler = self::request('/fr/products?untranslated=1&sort[name]=desc');

        self::assertSame(['Table', '1,250.50', 'Non', 'Published'], \array_slice($crawler->filter('tbody tr')->first()->filter('td')->each(static fn (Crawler $td) => $td->text()), 0, 4));
    }

    public function testRendersTheBootstrapTheme(): void
    {
        $crawler = self::request('/en/products?filter[q]=a', 'bootstrap');

        self::assertSame('ux-data-table__table table', $crawler->filter('table')->attr('class'));
        self::assertSame('btn btn-primary', $crawler->filter('form button')->attr('class'));
        self::assertSame('btn btn-link', $crawler->filter('form a')->attr('class'));
        self::assertSame('form-control', $crawler->filter('input[name="filter[q]"]')->attr('class'));
        self::assertCount(1, $crawler->filter('ul.pagination'));
    }

    public function testRendersTheActionsWithTheBootstrapTheme(): void
    {
        $crawler = self::request('/en/products?actions=1', 'bootstrap');

        self::assertSame('ux-data-table__toolbar d-flex flex-wrap gap-2 mb-3', $crawler->filter('.ux-data-table__toolbar')->attr('class'));
        self::assertSame('btn btn-sm btn-outline-secondary', $crawler->filter('.ux-data-table__toolbar a')->attr('class'));
        self::assertSame('form-check-input', $crawler->filter('tbody input[type=checkbox]')->attr('class'));
        self::assertSame('ux-data-table__confirmation border-0 rounded-3 shadow p-4', $crawler->filter('dialog')->attr('class'));
        self::assertSame('btn btn-link', $crawler->filter('dialog button[command=close]')->attr('class'));

        $dropdown = $crawler->filter('tbody .dropdown')->first();
        self::assertSame('dropdown', $dropdown->filter('button')->attr('data-bs-toggle'));
        self::assertSame('dropdown-item', $dropdown->filter('.dropdown-menu button')->attr('class'));
        // Bootstrap hides the menu on the click opening the dialog: it is rendered after the menu
        self::assertSame('product_data_table-0-more-duplicate-confirmation', $dropdown->filter('.dropdown-menu button[command=show-modal]')->attr('commandfor'));
        self::assertCount(0, $dropdown->filter('.dropdown-menu dialog'));
        self::assertSame('/products/Armchair/duplicate', $dropdown->filter('dialog#product_data_table-0-more-duplicate-confirmation form')->attr('action'));
        self::assertSame('btn btn-sm btn-outline-secondary', $dropdown->filter('dialog button[type=submit]')->attr('class'));
        self::assertSame('btn btn-link', $dropdown->filter('dialog button[command=close]')->attr('class'));
    }

    public function testRendersTheTablerTheme(): void
    {
        $crawler = self::request('/en/products?actions=1&filter[q]=a', 'tabler');

        self::assertCount(1, $crawler->filter('.ux-data-table.card > .card-header form.ux-data-table__filters'));
        self::assertSame('btn btn-primary', $crawler->filter('.card-header .ux-data-table__toolbar a')->attr('class'));
        self::assertSame('ux-data-table__table table card-table table-vcenter', $crawler->filter('.table-responsive table')->attr('class'));
        self::assertSame('btn btn-sm btn-ghost-secondary', $crawler->filter('tbody td.ux-data-table__row-actions a')->attr('class'));
        self::assertSame('btn btn-sm btn-ghost-secondary dropdown-toggle', $crawler->filter('tbody .dropdown button')->first()->attr('class'));
        self::assertSame('dropdown-item', $crawler->filter('tbody .dropdown-menu button')->attr('class'));
        self::assertCount(0, $crawler->filter('tbody .dropdown-menu dialog'));
        self::assertSame('btn btn-sm btn-ghost-secondary', $crawler->filter('tbody .dropdown > dialog button[type=submit]')->attr('class'));
        self::assertCount(1, $crawler->filter('.card-footer ul.pagination'));

        $crawler = self::request('/en/products?filter[q]=chair', 'tabler');
        self::assertCount(0, $crawler->filter('.card-footer'));
    }

    public function testRendersTheBlocksOfTheColumnTypes(): void
    {
        $crawler = self::request('/en/products', 'custom');

        self::assertSame(['Price', 'Total'], $crawler->filter('th.custom-number')->each(static fn (Crawler $th) => $th->text()));
    }

    public function testRendersWithOverriddenVariables(): void
    {
        self::bootKernel();
        $dataTable = self::getContainer()->get(DataTableFactory::class)->create(DataTableType::class, [['name' => 'Chair']]);
        $dataTable->submit([]);

        $html = self::getContainer()->get(Environment::class)
            ->createTemplate('{{ ux_data_table(view, {turbo_frame: true, name: "chairs"}) }}')
            ->render(['view' => $dataTable->createView()]);

        self::assertStringContainsString('<turbo-frame id="chairs"', $html);
    }

    public function testRendersALinkWithoutUrlAsText(): void
    {
        self::bootKernel();
        $dataTable = self::getContainer()->get(DataTableFactory::class)->createBuilder(DataTableType::class, [['url' => 'https://symfony.com'], ['url' => null]])
            ->addColumn('url', LinkColumnType::class)
            ->getDataTable();
        $dataTable->submit([]);

        $crawler = new Crawler(self::getContainer()->get(Environment::class)->createTemplate('{{ ux_data_table(view) }}')->render(['view' => $dataTable->createView()]));

        self::assertSame('https://symfony.com', $crawler->filter('tbody tr')->eq(0)->filter('a')->attr('href'));
        self::assertCount(0, $crawler->filter('tbody tr')->eq(1)->filter('a'));
        self::assertSame('', $crawler->filter('tbody tr')->eq(1)->filter('td')->text());
    }

    #[TestWith(['@UXDataTable/theme/default.html.twig'])]
    #[TestWith(['@UXDataTable/theme/bootstrap_5.html.twig'])]
    #[TestWith(['@UXDataTable/theme/tabler.html.twig'])]
    public function testTranslatesTheTranslatableLabelsWithoutTranslationDomain(string $theme): void
    {
        self::bootKernel();
        $builder = self::getContainer()->get(DataTableFactory::class)->createBuilder(DataTableType::class, [['name' => 'Chair']], ['translation_domain' => false]);
        $dataTable = $builder
            ->addColumn('name', options: ['label' => new TranslatableMessage('Published')])
            ->addRowAction('edit', options: ['href' => '/edit', 'label' => new TranslatableMessage('Published'), 'confirmation' => new TranslatableMessage('Published')])
            ->addRowAction('more', DropdownActionType::class, ['label' => 'Published', 'actions' => [
                $builder->createAction('archive', options: ['href' => '/archive', 'label' => new TranslatableMessage('Published')]),
            ]])
            ->getDataTable();
        $dataTable->submit([]);

        $crawler = new Crawler(self::getContainer()->get(Environment::class)->createTemplate('{{ ux_data_table(view, {theme: theme}) }}')->render(['view' => $dataTable->createView(), 'theme' => $theme]));

        self::assertSame('Online', trim($crawler->filter('thead th')->first()->text()));
        self::assertSame('Online', trim($crawler->filter('button[command=show-modal]')->text()));
        self::assertSame('Online', $crawler->filter('dialog p')->text());
        // A label that is not translatable is not translated
        self::assertSame('Published', trim($crawler->filter('summary, button[data-bs-toggle]')->text()));
        self::assertSame('Online', trim($crawler->filter('li a')->text()));
    }

    private static function request(string $uri, string $environment = 'test'): Crawler
    {
        self::ensureKernelShutdown();
        /** @var KernelBrowser $client */
        $client = self::createClient(['environment' => $environment]);
        $crawler = $client->request('GET', $uri);
        self::assertResponseIsSuccessful();

        return $crawler;
    }
}
