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

use Symfony\UX\Image\Renderer\RenderOptions;
use Symfony\UX\Image\Twig\ImageMarkup;
use Symfony\UX\Image\Twig\RenderOptionsFactory;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * The props shared by <twig:ux:image> and <twig:ux:picture>.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
trait ImageProps
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

    private function renderOptions(): RenderOptions
    {
        return RenderOptionsFactory::create(
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
    }
}
