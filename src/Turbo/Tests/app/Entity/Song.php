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
 * @ORM\Entity
 * @Broadcast(topics={"@='songs_by_artist_' ~ (entity.artist ? entity.artist.id : null)", "songs"})
 *
 * @author Rick Kuipers <rick@levelup-it.com>
 */
#[Broadcast(topics: ['@="songs_by_artist_" ~ (entity.artist ? entity.artist.id : null)', 'songs'])]
class Song
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    public $id;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $title = '';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Artist", inversedBy="songs")
     *
     * @var Artist|null
     */
    public $artist;
}
