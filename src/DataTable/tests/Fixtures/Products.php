<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures;

use Symfony\UX\DataTable\Tests\Fixtures\Entity\Category;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;

final class Products
{
    /**
     * @return list<Product>
     */
    public static function all(): array
    {
        $furniture = new Category('Furniture');

        return [
            new Product('Chair', 10, category: $furniture),
            new Product('Table', 1250.5, false, Status::Published, $furniture),
            new Product('Armchair', 30, status: Status::Published),
            new Product('Lamp', 20),
        ];
    }
}
