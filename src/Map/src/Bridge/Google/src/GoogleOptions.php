<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google;

use Symfony\UX\Map\Bridge\Google\Option\FullscreenControlOptions;
use Symfony\UX\Map\Bridge\Google\Option\GestureHandling;
use Symfony\UX\Map\Bridge\Google\Option\MapTypeControlOptions;
use Symfony\UX\Map\Bridge\Google\Option\StreetViewControlOptions;
use Symfony\UX\Map\Bridge\Google\Option\ZoomControlOptions;
use Symfony\UX\Map\MapOptionsInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class GoogleOptions implements MapOptionsInterface
{
    public function __construct(
        private ?string $mapId = null,
        private GestureHandling $gestureHandling = GestureHandling::AUTO,
        private ?string $backgroundColor = null,
        private bool $disableDoubleClickZoom = false,
        private bool $zoomControl = true,
        private ZoomControlOptions $zoomControlOptions = new ZoomControlOptions(),
        private bool $mapTypeControl = true,
        private MapTypeControlOptions $mapTypeControlOptions = new MapTypeControlOptions(),
        private bool $streetViewControl = true,
        private StreetViewControlOptions $streetViewControlOptions = new StreetViewControlOptions(),
        private bool $fullscreenControl = true,
        private FullscreenControlOptions $fullscreenControlOptions = new FullscreenControlOptions(),
    ) {
    }

    public function mapId(?string $mapId): self
    {
        $this->mapId = $mapId;

        return $this;
    }

    public function gestureHandling(GestureHandling $gestureHandling): self
    {
        $this->gestureHandling = $gestureHandling;

        return $this;
    }

    public function backgroundColor(?string $backgroundColor): self
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function doubleClickZoom(bool $enable = true): self
    {
        $this->disableDoubleClickZoom = !$enable;

        return $this;
    }

    public function zoomControl(bool $enable = true): self
    {
        $this->zoomControl = $enable;

        return $this;
    }

    public function zoomControlOptions(ZoomControlOptions $zoomControlOptions): self
    {
        $this->zoomControl = true;
        $this->zoomControlOptions = $zoomControlOptions;

        return $this;
    }

    public function mapTypeControl(bool $enable = true): self
    {
        $this->mapTypeControl = $enable;

        return $this;
    }

    public function mapTypeControlOptions(MapTypeControlOptions $mapTypeControlOptions): self
    {
        $this->mapTypeControl = true;
        $this->mapTypeControlOptions = $mapTypeControlOptions;

        return $this;
    }

    public function streetViewControl(bool $enable = true): self
    {
        $this->streetViewControl = $enable;

        return $this;
    }

    public function streetViewControlOptions(StreetViewControlOptions $streetViewControlOptions): self
    {
        $this->streetViewControl = true;
        $this->streetViewControlOptions = $streetViewControlOptions;

        return $this;
    }

    public function fullscreenControl(bool $enable = true): self
    {
        $this->fullscreenControl = $enable;

        return $this;
    }

    public function fullscreenControlOptions(FullscreenControlOptions $fullscreenControlOptions): self
    {
        $this->fullscreenControl = true;
        $this->fullscreenControlOptions = $fullscreenControlOptions;

        return $this;
    }

    public function toArray(): array
    {
        $array = [
            'mapId' => $this->mapId,
            'gestureHandling' => $this->gestureHandling->value,
            'backgroundColor' => $this->backgroundColor,
            'disableDoubleClickZoom' => $this->disableDoubleClickZoom,
        ];

        if ($this->zoomControl) {
            $array['zoomControlOptions'] = $this->zoomControlOptions->toArray();
        }

        if ($this->mapTypeControl) {
            $array['mapTypeControlOptions'] = $this->mapTypeControlOptions->toArray();
        }

        if ($this->streetViewControl) {
            $array['streetViewControlOptions'] = $this->streetViewControlOptions->toArray();
        }

        if ($this->fullscreenControl) {
            $array['fullscreenControlOptions'] = $this->fullscreenControlOptions->toArray();
        }

        return $array;
    }
}
