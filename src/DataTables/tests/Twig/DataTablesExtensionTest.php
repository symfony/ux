<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTables\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTables\Builder\DataTableBuilderInterface;
use Symfony\UX\DataTables\Tests\Kernel\TwigAppKernel;

class DataTablesExtensionTest extends TestCase
{
    public function testRenderDataTable(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var DataTableBuilderInterface $builder */
        $builder = $container->get('test.datatables.builder');

        $table = $builder->createDataTable('table');

        $table->setOptions([
            'columns' => [
                ['title' => 'Column 1'],
                ['title' => 'Column 2'],
            ],
            'data' => [
                ['Row 1 Column 1', 'Row 1 Column 2'],
                ['Row 2 Column 1', 'Row 2 Column 2'],
            ],
        ]);

        $rendered = $container->get('test.datatables.twig_extension')->renderDataTable(
            $table,
            ['data-controller' => 'mycontroller', 'class' => 'myclass']
        );

        $this->assertSame(
            expected: '<table id="table" data-controller="mycontroller symfony--ux-datatables--datatable" data-symfony--ux-datatables--datatable-view-value="&#x7B;&quot;columns&quot;&#x3A;&#x5B;&#x7B;&quot;title&quot;&#x3A;&quot;Column&#x20;1&quot;&#x7D;,&#x7B;&quot;title&quot;&#x3A;&quot;Column&#x20;2&quot;&#x7D;&#x5D;,&quot;data&quot;&#x3A;&#x5B;&#x5B;&quot;Row&#x20;1&#x20;Column&#x20;1&quot;,&quot;Row&#x20;1&#x20;Column&#x20;2&quot;&#x5D;,&#x5B;&quot;Row&#x20;2&#x20;Column&#x20;1&quot;,&quot;Row&#x20;2&#x20;Column&#x20;2&quot;&#x5D;&#x5D;&#x7D;" class="myclass"></table>',
            actual: $rendered
        );
    }
}
