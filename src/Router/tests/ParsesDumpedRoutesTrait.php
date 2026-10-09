<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests;

/**
 * @internal
 */
trait ParsesDumpedRoutesTrait
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private static function parseDumpedRoutes(string $file): array
    {
        $content = file_get_contents($file);
        $start = strpos($content, '{');

        return json_decode(substr($content, $start, strrpos($content, '}') - $start + 1), true, flags: \JSON_THROW_ON_ERROR);
    }
}
