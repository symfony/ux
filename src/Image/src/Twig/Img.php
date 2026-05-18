<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig;

use Symfony\UX\Image\Provider\ProviderRegistry;
use Symfony\UX\Image\Service\Transformer;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Responsive image Twig component.
 *
 * Usage:
 *   <twig:ux:img src="/images/hero.jpg" alt="Hero" width="100vw md:80vw" />
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
#[AsTwigComponent('ux:img', template: '@Image/components/img.html.twig')]
class Img
{
    public const EMPTY_GIF = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

    public string $src;
    public ?string $alt = null;
    public ?string $width = null;
    public ?int $height = null;
    public ?string $ratio = null;
    public ?string $fit = null;
    public ?string $focal = null;
    public ?int $quality = null;
    public ?string $format = null;
    public ?string $loading = null;
    public ?string $fetchpriority = null;
    public ?string $background = null;
    public ?string $fallback = null;
    public ?string $fallbackFormat = null;
    public ?string $class = null;
    public ?string $sizes = null;
    public ?string $srcset = null;
    public ?string $densities = null;
    public ?array $modifiers = null;
    public ?string $provider = null;
    public ?string $fallbackImage = null;

    /** @var array<string, mixed> */
    public array $extraAttributes = [];

    protected array $widths = [];
    public ?int $widthComputed = null;

    public function __construct(
        protected ProviderRegistry $providerRegistry,
        protected Transformer $transformer,
    ) {
    }

    public function mount(
        string $src,
        ?string $alt = null,
        ?string $width = null,
        ?int $height = null,
        ?string $ratio = null,
        ?string $fit = null,
        ?string $focal = null,
        ?int $quality = null,
        ?string $format = null,
        ?string $loading = 'lazy',
        ?string $fetchpriority = null,
        ?string $background = null,
        ?string $fallback = 'lg',
        ?string $fallbackFormat = 'auto',
        ?string $densities = null,
        ?array $modifiers = null,
        ?string $provider = null,
        ?string $class = null,
    ): void {
        $this->src = $src;
        $this->alt = $alt;
        $this->width = $width;
        $this->height = $height;
        $this->ratio = $ratio;
        $this->fit = $fit;
        $this->focal = $focal;
        $this->quality = $quality;
        $this->format = $format;
        $this->loading = $loading;
        $this->fetchpriority = $fetchpriority;
        $this->background = $background;
        $this->fallback = $fallback;
        $this->fallbackFormat = $fallbackFormat;
        $this->densities = $densities;
        $this->modifiers = $modifiers;
        $this->provider = $provider;
        $this->class = $class;

        if ($this->width) {
            $this->widths = $this->transformer->parseWidth($this->width);
            $this->widthComputed = $this->transformer->getInitialWidth($this->widths, $this->width);

            // Build fallback image (with fallback format)
            $this->fallbackImage = $this->getImage(['width' => $this->widthComputed], true);

            // Generate srcset
            if ($this->densities) {
                $this->handleDensities();
            } elseif (str_contains($this->width, 'vw') || str_contains($this->width, ':')) {
                $this->srcset = $this->transformer->getSrcset(
                    $this->src,
                    $this->widths,
                    fn ($m) => $this->getImage($m, false)
                );
                $this->sizes = $this->transformer->getSizes($this->widths);
            }
        } else {
            $this->fallbackImage = $this->getImage([], true);
        }
    }

    /**
     * Collects any data-* and aria-* attributes passed to the component.
     */
    public function preMount(array $data): array
    {
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'data-') || str_starts_with($key, 'aria-')) {
                $this->extraAttributes[$key] = $value;
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected function getImage(array $modifiers = [], bool $applyFallback = false): string
    {
        if ($this->modifiers) {
            $modifiers = array_merge($modifiers, $this->modifiers);
        }

        if ($this->format && !$applyFallback) {
            $modifiers['format'] = $this->format;
        } elseif ($applyFallback) {
            if ('empty' === $this->fallbackFormat) {
                return self::EMPTY_GIF;
            } elseif ('auto' === $this->fallbackFormat) {
                $ext = strtolower(pathinfo($this->src, \PATHINFO_EXTENSION));
                $modifiers['format'] = \in_array($ext, ['png', 'webp', 'gif'], true) ? 'png' : 'jpg';
            } elseif ($this->fallbackFormat) {
                $modifiers['format'] = $this->fallbackFormat;
            }
        }

        if (null !== $this->quality) {
            $modifiers['quality'] = $this->quality;
        }
        if (null !== $this->fit) {
            $modifiers['fit'] = $this->fit;
        }
        if (null !== $this->focal) {
            $modifiers['focal'] = $this->focal;
        }
        if (null !== $this->background) {
            $modifiers['background'] = $this->background;
        }
        if (null !== $this->ratio) {
            $modifiers['ratio'] = $this->ratio;
        }
        if (isset($modifiers['width'])) {
            $modifiers['width'] = (int) $modifiers['width'];
        }

        return $this->providerRegistry->getProvider($this->provider)->getImage($this->src, $modifiers);
    }

    protected function handleDensities(): void
    {
        if (!str_contains($this->width, 'vw') && !str_contains($this->width, ':')) {
            $densityWidths = $this->transformer->getDensityBasedWidths($this->widthComputed, $this->densities);
            $parts = [];
            foreach ($densityWidths as $w) {
                $parts[] = $this->getImage(['width' => $w], false).' '.$w.'w';
            }
            $this->srcset = implode(', ', $parts);
        } else {
            $densityWidths = $this->transformer->getDensityBasedWidths($this->widthComputed, $this->densities);
            $this->widths = array_unique(array_merge($this->widths, $densityWidths));
            sort($this->widths);
            $this->srcset = $this->transformer->getSrcset(
                $this->src,
                $this->widths,
                fn ($m) => $this->getImage($m, false)
            );
        }
    }

    /**
     * Returns the src attribute for the img element (fallback format).
     */
    public function getSrc(): string
    {
        if ($this->width && str_contains($this->width, 'vw')) {
            $bps = $this->transformer->getBreakpoints();
            return $this->getImage(['width' => $bps[$this->fallback]], true);
        }

        if ($this->width) {
            $widths = $this->transformer->parseWidth($this->width);
            $w = $widths['default'] ?? array_shift($widths);
            return $this->getImage(['width' => $w['value']], true);
        }

        return $this->getImage([], true);
    }

    /**
     * Returns the computed src (for the template).
     */
    public function getSrcComputed(): string
    {
        if ('empty' === $this->fallbackFormat) {
            return self::EMPTY_GIF;
        }

        if ($this->width) {
            $getFallback = str_contains($this->width, 'w');
            return $this->getImage(['width' => $this->widthComputed], $getFallback);
        }

        return $this->getImage([], false);
    }

    public function getHtmlWidth(): ?string
    {
        if ($this->width && preg_match('/^\d+$/', $this->width)) {
            return $this->width;
        }

        return null;
    }

    public function getHtmlHeight(): ?string
    {
        if (null !== $this->height) {
            return (string) $this->height;
        }

        return null;
    }
}
