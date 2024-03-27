<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class Game
{
    public \DateTimeImmutable $date;
    /**
     * @var Team[]
     */
    private array $teams = [];

    public function addTeam(Team $team): void
    {
        $this->teams[] = $team;
        $this->teams = array_values($this->teams);
    }

    public function removeTeam(Team $team): void
    {
        if (false === $i = array_search($team, $this->teams, true)) {
            return;
        }

        unset($this->teams[$i]);
        $this->teams = array_values($this->teams);
    }

    public function getTeams(): array
    {
        return $this->teams;
    }
}
