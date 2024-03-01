<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\LiveMemory;

use App\LiveMemory\Game;
use App\LiveMemory\GameEngine;
use App\LiveMemory\GameFactory;
use App\LiveMemory\GameStorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * @demo   LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsLiveComponent('LiveMemory:Board')]
class Board extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public ?Game $game = null;

    public function __construct(
        private readonly GameStorageInterface $gameStorage,
        private readonly GameFactory $gameFactory,
        private readonly GameEngine $gameEngine,
    ) {
    }

    /**
     * We guarantee that a game instance exists in the component, either from the session or a new one.
     *
     * For this demo, we do not save the game until the player flips its first card, allowing us to show a game
     * to any visitor without creating useless session.
     *
     * Priority set to 64 to ensure a game is available in all "public" methods: flipCard(), play(), refresh()
     *
     * @see flipCard()  // Called during a game to flip a card
     * @see play()      // Called after a game is completed (or in debug mode to switch level)
     * @see refresh()   // Called when the Timer has run out
     */
    #[PostHydrate(priority: 64)]
    #[PostMount(priority: 64)]
    public function initialize(): void
    {
        $this->game ??= $this->gameStorage->loadGame() ?? $this->gameFactory->createGame();
    }

    /**
     * On full refresh, create and store a new game if the current one is ended.
     *
     * We do not do this for partial refreshes (LiveAction or LiveListener) because
     * we want to keep the current game in the session and display the "score" screen.
     *
     * Priority set to 32 to be sure that initialize() has been called before.
     *
     * @see initialize()
     */
    #[PostMount(priority: 32)]
    public function postMount(): void
    {
        if ($this->game->isPlaying()) {
            $this->gameEngine->checkTime($this->game);
            if ($this->game->isEnded()) {
                // Save the game to display the "score" screen
                $this->gameStorage->saveGame($this->game);

                return;
            }
        }

        if ($this->game->isEnded()) {
            // More than 5 minutes since end of game ? Reset
            if ($this->game->getEndedAt()->modify('+5 minutes') < new \DateTimeImmutable('now')) {
                $this->game = $this->gameFactory->createGame();
                $this->gameStorage->saveGame($this->game);

                return;
            }

            // Same level if lost, next if completed (or first one if we reached the last level)
            $this->game = $this->gameFactory->createNextGame($this->game);
            $this->gameStorage->saveGame($this->game);
        }
    }

    /**
     * The main action of the game: flip a card in the current game.
     *
     * For this demo, we only store one game per player (in the session).
     * That is why we do not need to:
     * - check if the game exists
     * - check if the game is the current one
     * - check if the player is allowed to play it
     *
     * The game logic (what happens when a card is flipped, when a pair is matched,
     * when the game is completed, etc.) is delegated to the GameEngine service.
     *
     * @see GameEngine::flipCard()
     */
    #[LiveAction]
    public function flip(#[LiveArg('key')] int $key): void
    {
        if (!isset($this->game->getCards()[$key])) {
            // An old game should have stopped, so this should never happen
            // Even if the player clicked "too late" on a card, the game would not
            // be completed... meaning the card would still exist in the new game.
            throw new \RuntimeException(sprintf('Game "%s" has no card "%s".', $this->game->getId(), $key));
        }

        // As we show "unsaved games" as initial content in the template view,
        // we save the game only after the player flips its first card.
        if (!$this->game->isStarted()) {
            $this->game->setStartedAt(new \DateTimeImmutable());
        }

        $this->gameEngine->flipCard($this->game, $key);
        $this->gameStorage->saveGame($this->game);
    }

    #[LiveAction]
    public function play(#[LiveArg] int $level = 1): void
    {
        // We allow any level to be played, even if it's not the next one,
        // to facilitate the demo. In real life we would have:
        // - checked that the level is the next one
        // - checked that the user has completed the previous level
        $this->game = $this->gameFactory->createGame($level);
        $this->gameStorage->saveGame($this->game);
    }

    #[LiveListener('LiveMemory:Timer:TimeOut')]
    public function refresh(): void
    {
        // If there is no game in progress, we do nothing
        if (!$this->game->isPlaying()) {
            return;
        }

        $this->gameEngine->checkTime($this->game);
        if ($this->game->isEnded()) {
            // Save the game to display the "score" screen
            $this->gameStorage->saveGame($this->game);
        }
    }

    #[ExposeInTemplate('cards')]
    public function getCards(): array
    {
        $cards = [];
        $matches = $this->game->getMatches();
        $currentPair = $this->game->getCurrentPair();
        foreach ($this->game->getCards() as $key => $card) {
            $cards[$key] = [
                'image' => $card,
                'selected' => $selected = \in_array($key, $currentPair),
                'paired' => $selected && 2 === \count($currentPair),
                'matched' => $matched = \in_array($key, $matches),
                'flipped' => $selected || $matched,
            ];
        }

        return $cards;
    }
}
