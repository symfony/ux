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
 * @author Rick Kuipers <rick@levelup-it.com>
 */
#[Broadcast(topics: ['@="songs_by_artist_" ~ (entity.artist ? entity.artist.id : null)', 'songs'])]
#[ORM\Entity]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public ?string $id = null;

    #[ORM\Column]
    public string $title = '';

    #[ORM\ManyToOne(targetEntity: Artist::class, inversedBy: 'songs')]
    public ?Artist $artist = null;
}
