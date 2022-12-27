<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\BootstrapTable\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\BootstrapTable\Builder\TableBuilderInterface;
use Symfony\UX\BootstrapTable\Tests\Kernel\TwigAppKernel;
use Twig\Environment;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class BootstrapTableExtensionTest extends TestCase
{
    public function testRenderTable(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        /** @var TableBuilderInterface $builder */
        $builder = $container->get('test.bootstrapTable.builder');

        $table = $builder
            ->addColumns([
                ['title' => 'ID', 'field' => 'id'],
                ['title' => 'Pseudo', 'field' => 'pseudo'],
            ])
            ->addData([
                ['id' => 1, 'pseudo' => 'Bob'],
                ['id' => 2, 'pseudo' => 'Kitty'],
            ])
            ->addOptions([
                'pagination' => 'true',
                'search' => 'true',
            ])
            ->createTable()
        ;

        $rendered = $container
            ->get('test.bootstrapTable.twig_extension')
            ->renderTable(
                $container->get(Environment::class),
                $table
            )
        ;

        $this->assertSame(
            '<table data-controller="symfony--bootstrap-table--table" data-symfony--bootstrap-table--table-rows-value="&#x5B;&#x7B;&quot;id&quot;&#x3A;1,&quot;pseudo&quot;&#x3A;&quot;Bob&quot;&#x7D;,&#x7B;&quot;id&quot;&#x3A;2,&quot;pseudo&quot;&#x3A;&quot;Kitty&quot;&#x7D;&#x5D;" data-symfony--bootstrap-table--table-columns-value="&#x5B;&#x7B;&quot;title&quot;&#x3A;&quot;ID&quot;,&quot;field&quot;&#x3A;&quot;id&quot;&#x7D;,&#x7B;&quot;title&quot;&#x3A;&quot;Pseudo&quot;,&quot;field&quot;&#x3A;&quot;pseudo&quot;&#x7D;&#x5D;" data-symfony--bootstrap-table--table-options-value="&#x7B;&quot;pagination&quot;&#x3A;&quot;true&quot;,&quot;search&quot;&#x3A;&quot;true&quot;&#x7D;"></table>',
            $rendered);
    }
}
