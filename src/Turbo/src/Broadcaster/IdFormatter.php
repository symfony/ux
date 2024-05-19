<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Broadcaster;

/**
 * Formats an id array to a string.
 *
 * In defaults the id array is something like `['id' => 1]` or `['uuid' => '00000000-0000-0000-0000-000000000000']`.
 * For a composite key it could be something like `['cart' => ['id' => 1], 'product' => ['id' => 1]]`.
 *
 * To create a string representation of the id, the values of the array are flattened and concatenated with a dash.
 *
 * @author Jason Schilling <jason@sourecode.dev>
 */
class IdFormatter
{
    /**
     * @param array<string, array<string, string>>|array<string, string>|string $id
     */
    public function format(array|string $id): string
    {
        if (\is_string($id)) {
            return $id;
        }

        $flatten = [];

        array_walk_recursive($id, static function ($item) use (&$flatten) { $flatten[] = $item; });

        return implode('-', $flatten);
    }
}
