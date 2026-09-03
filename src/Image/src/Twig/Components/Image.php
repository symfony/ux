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

use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Renderer\ImageRenderOptions;
use Symfony\UX\Image\Renderer\RenderedImage;
use Symfony\UX\Image\Twig\ImageRuntime;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Twig\Extra\Html\HtmlExtension;

/**
 * Twig component for rendering images with responsive features.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsTwigComponent('ux:image')]
final class Image
{
    public ?ImageAsset $src = null;
    public ?string $alt = null;
    public ?string $class = null;
    public ?string $variant = null;
    public bool $lazy = true;
    /** @var list<string>|null */
    public ?array $srcset = null;
    public ?string $sizes = null;
    public ?string $fetchpriority = null;
    public ?string $decoding = 'async';

    private ?RenderedImage $rendered = null;

    public function __construct(private readonly ImageRuntime $runtime)
    {
    }

    #[ExposeInTemplate]
    public function rendered(array $attributes = []): ?RenderedImage
    {
        if (!$this->src) {
            return null;
        }

        foreach ($attributes as $name => $value) {
            $attributes[$name] = HtmlExtension::htmlAttrValue($name, $value);
        }

        return $this->rendered ??= $this->runtime->render($this->src, new ImageRenderOptions(
            sizes: $this->sizes,
            alt: $this->alt ?? '',
            lazy: $this->lazy,
            fetchPriority: $this->fetchpriority ?? ($this->lazy ? 'auto' : 'high'),
            class: $this->class ?? '',
            decoding: $this->decoding ?? 'async',
            variant: $this->variant,
            srcset: $this->srcset,
            attributes: $attributes,
        ));
    }
}
