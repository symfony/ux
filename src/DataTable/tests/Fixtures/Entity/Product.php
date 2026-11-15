<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\DataTable\Tests\Fixtures\Status;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column]
        public string $name,
        #[ORM\Column]
        public float $price = 0.0,
        #[ORM\Column]
        public bool $active = true,
        #[ORM\Column(enumType: Status::class)]
        public Status $status = Status::Draft,
        #[ORM\ManyToOne]
        public ?Category $category = null,
        #[ORM\Embedded]
        public Dimensions $dimensions = new Dimensions(),
    ) {
    }
}
