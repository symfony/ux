<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Renderer;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
interface RendererFactoryInterface
{
    public function create(Dsn $dsn): RendererInterface;

    public function supports(Dsn $dsn): bool;
}
