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

use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\Image\Twig\ImageMarkup;
use Symfony\UX\Image\Twig\RenderOptionsFactory;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Twig\Markup;

/**
 * Backs the <twig:ux:image> component.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Image
{
    public string $src;

    public string $alt;

    public string $layout = 'constrained';

    public ?int $width = null;

    public ?int $height = null;

    public ?string $fit = null;

    public ?string $format = null;

    public ?int $quality = null;

    public bool $priority = false;

    public ?string $objectFit = null;

    /**
     * @var list<int>|null
     */
    public ?array $breakpoints = null;

    /**
     * @var array<string, array<string, scalar>>
     */
    public array $operations = [];

    public function __construct(
        private readonly ImageRendererInterface $renderer,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    #[PostMount]
    public function normalizeStyleAttribute(array $attributes): array
    {
        return ImageMarkup::normalizeAttributes($attributes);
    }

    public function html(ComponentAttributes $attributes): Markup
    {
        $options = RenderOptionsFactory::create(
            layout: $this->layout,
            width: $this->width,
            height: $this->height,
            fit: $this->fit,
            format: $this->format,
            quality: $this->quality,
            priority: $this->priority,
            objectFit: $this->objectFit,
            breakpoints: $this->breakpoints,
            operations: $this->operations,
        );

        return new Markup(ImageMarkup::render($this->renderer->render($this->src, $this->alt, $options), $attributes), 'UTF-8');
    }
}
