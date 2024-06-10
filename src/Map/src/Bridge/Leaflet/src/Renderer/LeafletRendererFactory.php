<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Leaflet\Renderer;

use Symfony\UX\Map\Exception\UnsupportedSchemeException;
use Symfony\UX\Map\Renderer\AbstractRendererFactory;
use Symfony\UX\Map\Renderer\Dsn;
use Symfony\UX\Map\Renderer\RendererFactoryInterface;
use Symfony\UX\Map\Renderer\RendererInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class LeafletRendererFactory extends AbstractRendererFactory implements RendererFactoryInterface
{
    public function create(Dsn $dsn): RendererInterface
    {
        if (!$this->supports($dsn)) {
            throw new UnsupportedSchemeException($dsn);
        }

        return new LeafletRenderer($this->stimulus);
    }

    protected function getSupportedSchemes(): array
    {
        return ['leaflet'];
    }
}
