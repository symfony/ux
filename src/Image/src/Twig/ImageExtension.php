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

use Symfony\UX\Image\ImageAsset;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides `ux_image` and `ux_picture` Twig functions to render
 * responsive image elements from an ImageAsset object.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly ImageRuntime $imageRuntime,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_image', [$this, 'renderImage'], ['is_safe' => ['html']]),
            new TwigFunction('ux_picture', [$this, 'renderPicture'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renders the image component.
     *
     * @param ImageAsset           $asset   The image asset information
     * @param array<string, mixed> $options Options include:
     *                                      - sizes: sizes attribute value (string)
     *                                      - alt: alt attribute (string)
     *                                      - lazy: bool to enable loading="lazy"
     *                                      - fetchpriority: fetchpriority attribute (auto|low|high)
     *                                      - decoding: decoding attribute (async|sync|auto)
     *                                      - class: css classes
     *                                      - variant: name of the variant to filter sources by (string)
     */
    public function renderImage(ImageAsset $asset, array $options = []): string
    {
        $rendered = $this->imageRuntime->renderConfigured($asset, $options);

        return $rendered->toImgHtml();
    }

    /**
     * Renders the <picture> component with responsive sources.
     *
     * @param array<string, mixed> $options
     */
    public function renderPicture(ImageAsset $asset, array $options = []): string
    {
        return $this->imageRuntime->renderConfigured($asset, $options)->toHtml();
    }
}
