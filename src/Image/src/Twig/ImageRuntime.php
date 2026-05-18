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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension providing the {{ ux_image() }} function.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
class ImageRuntime extends AbstractExtension
{
    public function __construct(
        private readonly ProviderRegistry $providerRegistry,
        private readonly Transformer $transformer,
        private readonly array $defaults = [],
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_image', [$this, 'renderImage']),
        ];
    }

    /**
     * Renders a responsive image tag.
     *
     * @param string      $src       Image source path
     * @param string|null $alt       Alt text
     * @param array       $options   Additional options (width, ratio, format, etc.)
     */
    public function renderImage(string $src, ?string $alt = null, array $options = []): string
    {
        $provider = $this->providerRegistry->getProvider($options['provider'] ?? null);
        $modifiers = [];

        if (isset($options['width'])) {
            $widths = $this->transformer->parseWidth($options['width']);
            $initialWidth = $this->transformer->getInitialWidth($widths, $options['width']);
            $modifiers['width'] = $initialWidth;
        }

        if (isset($options['format'])) {
            $modifiers['format'] = $options['format'];
        }
        if (isset($options['quality'])) {
            $modifiers['quality'] = $options['quality'];
        }
        if (isset($options['fit'])) {
            $modifiers['fit'] = $options['fit'];
        }
        if (isset($options['focal'])) {
            $modifiers['focal'] = $options['focal'];
        }
        if (isset($options['ratio'])) {
            $modifiers['ratio'] = $options['ratio'];
        }
        if (isset($options['background'])) {
            $modifiers['background'] = $options['background'];
        }

        $imageUrl = $provider->getImage($src, $modifiers);

        $attributes = [
            'src' => htmlspecialchars($imageUrl, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
        ];

        if (null !== $alt) {
            $attributes['alt'] = htmlspecialchars($alt, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        }

        // Generate srcset if width is responsive
        if (isset($options['width']) && (str_contains($options['width'], 'vw') || str_contains($options['width'], ':'))) {
            $widths = $this->transformer->parseWidth($options['width']);
            $srcset = $this->transformer->getSrcset($src, $widths,
                fn ($m) => $provider->getImage($src, array_merge($modifiers, $m))
            );
            $attributes['srcset'] = htmlspecialchars($srcset, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
            $attributes['sizes'] = htmlspecialchars($this->transformer->getSizes($widths), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        }

        if (isset($options['loading'])) {
            $attributes['loading'] = htmlspecialchars($options['loading'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        }
        if (isset($options['class'])) {
            $attributes['class'] = htmlspecialchars($options['class'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        }

        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= \sprintf(' %s="%s"', $key, $value);
        }

        return \sprintf('<img%s>', $attrString);
    }
}
