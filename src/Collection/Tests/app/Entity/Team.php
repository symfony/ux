<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class Team
{
    public string $name;
    /**
     * @var Player[]
     */
    private array $players = [];

    public function addPlayer(Player $player): void
    {
        $this->players[] = $player;
        $this->players = array_values($this->players);
    }

    public function removePlayer(Player $player): void
    {
        if (false === $i = array_search($player, $this->players, true)) {
            return;
        }

        unset($this->players[$i]);
        $this->players = array_values($this->players);
    }

    public function getPlayers(): array
    {
        return $this->players;
    }
}
