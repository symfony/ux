<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

use Symfony\UX\Toolkit\Kit\Kit;

/**
 * @internal
 *
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
interface RegistryInterface
{
    public static function supports(string $kitName): bool;

    /**
     * @throws \RuntimeException if the kit does not exist
     */
    public function getKit(string $kitName): Kit;
}
