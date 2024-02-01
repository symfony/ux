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

use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

/**
 * This class hydrate the Game object from/to the LiveMemory:Board component.
 *
 * @see https://symfony.com/doc/current/ux/live-component.html#hydration
 *
 * @demo LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class GameHydrationExtension implements HydrationExtensionInterface
{
    public function __construct(
        private readonly GameStorageInterface $gameStorage,
    ) {
    }

    public function supports(string $className): bool
    {
        return Game::class === $className;
    }

    public function hydrate(mixed $value, string $className): ?object
    {
        if (!\is_string($value)) {
            return null;
        }

        $game = $this->gameStorage->loadGame();
        if ($game?->getId() !== $value) {
            return null;
        }

        return $game;
    }

    /**
     * Returns a scalar value that can be used to hydrate the object later.
     *
     * @param Game $object
     */
    public function dehydrate(object $object): string
    {
        return (string) $object->getId();
    }
}
