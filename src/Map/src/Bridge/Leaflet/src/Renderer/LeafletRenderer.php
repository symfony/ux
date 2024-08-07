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

use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\MapOptionsInterface;
use Symfony\UX\Map\Renderer\AbstractRenderer;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final readonly class LeafletRenderer extends AbstractRenderer
{
    protected function getName(): string
    {
        return 'leaflet';
    }

    protected function getProviderOptions(): array
    {
        return [];
    }

    protected function getDefaultMapOptions(): MapOptionsInterface
    {
        return new LeafletOptions();
    }

    public function __toString(): string
    {
        return 'leaflet://default';
    }
}
