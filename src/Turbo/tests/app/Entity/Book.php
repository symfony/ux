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

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
#[Broadcast]
#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column]
    public string $title = '';
}
