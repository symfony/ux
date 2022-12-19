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
 *
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
            ->addColumns(['id', 'pseudo'])
            ->addData([
                ['id' => 1, 'pseudo' => 'Bob'],
                ['id' => 2, 'pseudo' => 'Kitty']
            ])
            ->addColumnsAttributes([
                'id' => ['data-align' => 'right']
            ])
            ->addTableAttributes([
                'data-search' => 'true'
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
            '<table data-toggle="table" data-search="true" ><thead><tr><th data-align="right" >id</th><th >pseudo</th></tr></thead><tbody><tr><td>1</td><td>Bob</td></tr><tr><td>2</td><td>Kitty</td></tr></tbody></table>',
            $rendered);
    }
}
