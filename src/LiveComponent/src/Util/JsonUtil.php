<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
final class JsonUtil
{
    public static function encodeObject($data): string
    {
        if (0 === \count($data)) {
            return '{}';
        }

        return json_encode($data, \JSON_THROW_ON_ERROR);
    }
}
