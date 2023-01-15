<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Core\Tests\Kernel;

use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTable\Core\TableBuilder;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class TableBuilderTest extends TestCase
{
    public function testBuildTable(): void
    {
        $builder = new TableBuilder();

        $table = $builder
            ->addData(['id' => 1, 'name' => 'Fabien'])
            ->addColumns(['name'])
            ->addOptions([])
            ->createTable()
        ;

        $this->assertSame($table->getData(), ['id' => 1, 'name' => 'Fabien']);
        $this->assertSame($table->getColumns(), ['name']);
        $this->assertSame($table->getOptions(), []);
    }
}
