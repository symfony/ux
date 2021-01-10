<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Broadcast;

/**
 * @ORM\Entity
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
#[Broadcast]
class Book
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public ?int $id = null;

    /**
     * @ORM\Column
     */
    public string $title = '';
}
