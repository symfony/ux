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

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

/**
 * @author Rick Kuipers <rick@levelup-it.com>
 */
#[Broadcast]
#[ORM\Entity]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';

    /**
     * @var Collection<int, Song>
     */
    #[ORM\OneToMany(targetEntity: Song::class, mappedBy: 'artist')]
    public Collection $songs;
}
