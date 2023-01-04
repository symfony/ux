<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTable\Builder\TableBuilderInterface;
use Symfony\UX\DataTable\Tests\Kernel\TwigAppKernel;
use Twig\Environment;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class DataTableExtensionTest extends TestCase
{
    public function testRenderTable(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var TableBuilderInterface $builder */
        $builder = $container->get('test.datatable.builder');

        $table = $builder
            ->addData([
                ['id' => 1, 'pseudo' => 'Bob'],
                ['id' => 2, 'pseudo' => 'Kitty'],
            ])
            ->addOptions([
                'paging' => false,
                'searchable' => false,
            ])
            ->createTable()
        ;

        $rendered = $container
            ->get('test.datatable.twig_extension')
            ->renderTable(
                $container->get(Environment::class),
                $table
            )
        ;

        $this->assertSame(
            '<table data-controller="symfony--ux-datatable--table" data-symfony--ux-datatable--table-rows-value="&#x5B;&#x7B;&quot;id&quot;&#x3A;1,&quot;pseudo&quot;&#x3A;&quot;Bob&quot;&#x7D;,&#x7B;&quot;id&quot;&#x3A;2,&quot;pseudo&quot;&#x3A;&quot;Kitty&quot;&#x7D;&#x5D;" data-symfony--ux-datatable--table-columns-value="&#x5B;&#x5D;" data-symfony--ux-datatable--table-options-value="&#x7B;&quot;paging&quot;&#x3A;false,&quot;searchable&quot;&#x3A;false&#x7D;"></table>',
            $rendered);
    }
}
