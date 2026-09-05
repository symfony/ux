<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Provider;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
interface ProviderFactoryInterface
{
    public function create(Dsn $dsn): ProviderInterface;

    public function supports(Dsn $dsn): bool;
}
