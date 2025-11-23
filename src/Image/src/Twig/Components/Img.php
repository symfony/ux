<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig\Components;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Image\Provider\ProviderRegistry;
use Symfony\UX\Image\Service\PreloadManager;
use Symfony\UX\Image\Service\Transformer;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

/**
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
#[AsTwigComponent('img', template: '@Image/components/img.html.twig')]
class Img
{
    public const EMPTY_GIF = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';

    public string $src;
    public ?string $alt = null;
    public ?string $width = null;
    public ?int $widthComputed = null;
    public ?int $height = null;
    public ?string $ratio = null;
    public ?string $fit = null;
    public ?string $focal = null;
    public ?string $quality = null;
    public ?string $format = null;
    public ?string $loading = null;
    public ?string $fetchpriority = null;
    public ?bool $preload = null;
    public ?string $background = null;
    public ?string $fallback = null;
    public ?string $fallbackFormat = null;
    public ?string $class = null;
    public ?string $preset = null;
    public ?string $placeholder = null;
    public ?string $placeholderClass = null;
    public ?string $sizes = null;
    public ?string $srcset = null;
    public ?string $densities = null;
    public ?array $modifiers = null;
    public ?string $provider = null;
    public ?string $fallbackImage = null;

    protected array $widths = [];

    public function __construct(
        protected ParameterBagInterface $params,
        protected ProviderRegistry $providerRegistry,
        protected Transformer $transformer,
        protected PreloadManager $preloadManager,
    ) {
        $this->params = $params;
        $defaults = $this->params->get('ux_image.defaults');
        $this->fallback = $defaults['fallback'];
        $this->fallbackFormat = $defaults['fallback_format'];
    }

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setDefined([
                'alt',
                'width',
                'height',
                'ratio',
                'fit',
                'focal',
                'quality',
                'loading',
                'fetchpriority',
                'preload',
                'background',
                'fallback',
                'fallbackFormat',
                'fallback-format',
                'class',
                'preset',
                'placeholder',
                'placeholderClass',
                'placeholder-class',
                'srcset',
                'id',
                'referrerpolicy',
                'sizes',
                'style',
                'title',
                'crossorigin',
                'decoding',
                'format',
                'densities',
                'modifiers',
                'provider',
            ]);

        // Allow any data-* and aria-* attributes
        $resolver->setDefaults([]);
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'data-') || str_starts_with($key, 'aria-')) {
                $resolver->setDefined($key);
                $resolver->setAllowedTypes($key, ['string', 'null']);
            }
        }

        $resolver->setRequired('src');

        $resolver->setAllowedTypes('src', 'string');
        $resolver->setAllowedTypes('alt', ['string', 'null']);
        $resolver->setAllowedTypes('width', ['string', 'int', 'null']);
        $resolver->setAllowedTypes('height', ['int', 'null']);
        $resolver->setAllowedTypes('ratio', ['string', 'null']);
        $resolver->setAllowedTypes('fit', ['string', 'null']);
        $resolver->setAllowedTypes('focal', ['string', 'null']);
        $resolver->setAllowedTypes('quality', ['string', 'null']);
        $resolver->setAllowedTypes('loading', ['string', 'null']);
        $resolver->setAllowedTypes('fetchpriority', ['string', 'null']);
        $resolver->setAllowedTypes('preload', ['bool', 'null']);
        $resolver->setAllowedTypes('background', ['string', 'null']);
        $resolver->setAllowedTypes('fallback', ['string', 'null']);
        $resolver->setAllowedTypes('fallback-format', ['string', 'null']);
        $resolver->setAllowedTypes('class', ['string', 'null']);
        $resolver->setAllowedTypes('preset', ['string', 'null']);
        $resolver->setAllowedTypes('placeholder', ['string', 'null']);
        $resolver->setAllowedTypes('placeholder-class', ['string', 'null']);
        $resolver->setAllowedTypes('sizes', ['string', 'null']);
        $resolver->setAllowedTypes('id', ['string', 'null']);
        $resolver->setAllowedTypes('referrerpolicy', ['string', 'null']);
        $resolver->setAllowedTypes('style', ['string', 'null']);
        $resolver->setAllowedTypes('title', ['string', 'null']);
        $resolver->setAllowedTypes('crossorigin', ['string', 'null']);
        $resolver->setAllowedTypes('decoding', ['string', 'null']);
        $resolver->setAllowedTypes('densities', ['string', 'null']);
        $resolver->setAllowedTypes('modifiers', ['array', 'null']);
        $resolver->setAllowedTypes('provider', ['string', 'null']);

        if (isset($data['preset'])) {
            $presetName = $data['preset'];
            $presets = $this->params->get('ux_image.presets');

            if (isset($presets[$presetName])) {
                $data = array_merge($presets[$presetName], $data);
            }
        }

        // Normalize width value but preserve original format
        if (isset($data['width'])) {
            if (is_numeric($data['width'])) {
                $data['width'] = (string) $data['width'];
            }
        }

        if (isset($data['fallback-format'])) {
            $data['fallbackFormat'] = $data['fallback-format'];
            unset($data['fallback-format']);
        }

        return $resolver->resolve($data) + $data;
    }

    public function mount(
        string $src,
        $width = null,
        ?bool $preload = null,
        ?string $format = null,
        ?string $quality = null,
        ?string $fit = null,
        ?string $focal = null,
        ?string $fallback = null,
        ?string $background = null,
        ?string $ratio = null,
        ?string $densities = null,
        ?array $modifiers = null,
        ?string $provider = null,
        ?string $fallbackFormat = null,
    ): void {
        if (empty($src)) {
            throw new \InvalidArgumentException('Image src cannot be empty');
        }

        $this->src = $src;
        $this->width = $width;
        $this->format = $format;
        $this->quality = $quality;
        $this->fit = $fit;
        $this->focal = $focal;
        $this->fallback = $fallback;
        $this->background = $background;
        $this->ratio = $ratio;
        $this->densities = $densities;
        $this->modifiers = $modifiers;
        $this->provider = $provider;

        if (null !== $fallbackFormat) {
            $this->fallbackFormat = $fallbackFormat;
        }

        if (null !== $preload) {
            $this->preload = $preload;
        }

        if ($this->width) {
            // Get sizes from transformer
            $this->widths = $this->transformer->parseWidth($this->width);

            // Use new transformer method to determine initial width
            $this->widthComputed = $this->transformer->getInitialWidth($this->widths, $this->width);

            // For the main src, use the specified format
            $this->fallbackImage = $this->getImage(['width' => $this->widthComputed], false);

            // For srcset images, also use specified format
            if ($this->densities) {
                if (!str_contains($this->width, 'vw') && !str_contains($this->width, ':')) {
                    // For fixed widths, get density-based widths
                    $widthsForSrcset = $this->transformer->getDensityBasedWidths($this->widthComputed, $this->densities);

                    // Build srcset manually for fixed widths - don't apply fallback for srcset
                    $srcsetParts = [];
                    foreach ($widthsForSrcset as $w) {
                        $srcsetParts[] = $this->getImage(['width' => $w], false).' '.$w.'w';
                    }
                    $this->srcset = implode(', ', $srcsetParts);
                } else {
                    // For responsive widths, merge with density-based widths
                    $densityWidths = $this->transformer->getDensityBasedWidths($this->widthComputed, $this->densities);
                    $this->widths = array_unique(array_merge($this->widths, $densityWidths));
                    sort($this->widths);

                    // Generate srcset with all widths
                    $this->srcset = $this->transformer->getSrcset(
                        $this->src,
                        $this->widths,
                        fn ($modifiers) => $this->getImage($modifiers, false)
                    );
                }
            }
            // Generate srcset and sizes for responsive widths or breakpoint patterns
            elseif (str_contains($this->width, 'vw') || str_contains($this->width, ':')) {
                $this->srcset = $this->transformer->getSrcset(
                    $this->src,
                    $this->widths,
                    fn ($modifiers) => $this->getImage($modifiers, false)
                );
                $this->sizes = $this->transformer->getSizes($this->widths);
            }
        } else {
            $this->fallbackImage = $this->getImage([], true);
        }

        if ($this->preload) {
            $this->preloadManager->addPreloadImage($this->fallbackImage, [
                'srcset' => $this->srcset,
                'sizes' => $this->sizes,
            ]);
        }
    }

    protected function getImage(array $modifiers = [], bool $applyFallback = false): string
    {
        // Add custom modifiers if they exist
        if ($this->modifiers) {
            $modifiers = array_merge($modifiers, $this->modifiers);
        }

        // First apply the explicitly specified format if it exists
        if ($this->format && !$applyFallback) {
            $modifiers['format'] = $this->format;
        }
        // Then handle fallback formats if we're in fallback mode
        elseif ($applyFallback) {
            if ('empty' === $this->fallbackFormat) {
                return self::EMPTY_GIF;
            } elseif ('auto' === $this->fallbackFormat) {
                // Auto fallback logic based on original image format
                $extension = $this->getImageExtension();
                $modifiers['format'] = \in_array($extension, ['png', 'webp', 'gif']) ? 'png' : 'jpg';
            } elseif ($this->fallbackFormat) {
                $modifiers['format'] = $this->fallbackFormat;
            } elseif ($this->fallback) {
                // If fallback is specified, use that format
                $modifiers['format'] = $this->fallback;
            }
        }

        // Add other modifiers
        if ($this->quality) {
            $modifiers['quality'] = $this->quality;
        }

        if ($this->fit) {
            $modifiers['fit'] = $this->fit;
        }

        if ($this->focal) {
            $modifiers['focal'] = $this->focal;
        }

        if ($this->background) {
            $modifiers['background'] = $this->background;
        }

        if ($this->ratio) {
            $modifiers['ratio'] = $this->ratio;
        }

        if (isset($modifiers['width'])) {
            $modifiers['width'] = (int) $modifiers['width'];
        }

        return $this->providerRegistry->getProvider($this->provider)->getImage($this->src, $modifiers);
    }

    protected function getImageExtension(): string
    {
        return strtolower(pathinfo($this->src, \PATHINFO_EXTENSION));
    }

    public function getSrc(): string
    {
        if (str_contains($this->width, 'vw')) {
            $breakpoints = $this->transformer->getBreakpoints();

            return $this->getImage(['width' => $breakpoints[$this->fallback]], true);
        }

        $widths = $this->transformer->parseWidth($this->width);
        $width = $widths['default'] ?? array_shift($widths);

        return $this->getImage(['width' => $width['value']], true);
    }

    public function getSrcComputed(): string
    {
        // For empty fallback, return empty GIF
        if ('empty' === $this->fallbackFormat) {
            return self::EMPTY_GIF;
        }

        // For other cases, return the fallback image with fallback format
        if ($this->width) {
            $getFallback = str_contains($this->width, 'w') ? true : false;

            return $this->getImage(['width' => $this->widthComputed], $getFallback);
        }

        return $this->getImage([], false);
    }

    /**
     * Get width as HTML attribute (only if it's a simple numeric value).
     */
    public function getHtmlWidth(): ?string
    {
        if ($this->width && preg_match('/^\d+$/', $this->width)) {
            return $this->width;
        }

        return null;
    }

    /**
     * Get height as HTML attribute (only if it's a simple numeric value).
     */
    public function getHtmlHeight(): ?string
    {
        if ($this->height && preg_match('/^\d+$/', (string) $this->height)) {
            return (string) $this->height;
        }

        return null;
    }
}
