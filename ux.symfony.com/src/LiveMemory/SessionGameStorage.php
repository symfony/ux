<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\LiveMemory;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @demo LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class SessionGameStorage implements GameStorageInterface
{
    private const SESSION_KEY = 'live_memory.game';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function loadGame(): ?Game
    {
        return $this->getSession()->get(self::SESSION_KEY);
    }

    public function saveGame(?Game $game): void
    {
        if (null === $game) {
            $this->getSession()->remove(self::SESSION_KEY);

            return;
        }

        $this->getSession()->set(self::SESSION_KEY, $game);
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
