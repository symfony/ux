<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Storage;

/**
 * Interface for storage backends that support pruning temporary uploads.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface PrunableStorageInterface
{
    /**
     * Prune expired completed files and stale incomplete sessions.
     *
     * @param int $maxAge Maximum age in seconds
     */
    public function prune(int $maxAge): void;
}
