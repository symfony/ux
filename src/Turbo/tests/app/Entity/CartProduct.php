<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
#[Broadcast]
#[ORM\Entity]
class CartProduct
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Cart::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?Cart $cart = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?Product $product = null;

    #[ORM\Column(type: Types::INTEGER)]
    public int $quantity = 1;
}
