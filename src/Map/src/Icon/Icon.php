<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Icon;

use Symfony\UX\Map\Exception\InvalidArgumentException;

/**
 * Represents an icon that can be displayed on a map marker.
 *
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract class Icon
{
    /**
     * Creates a new icon based on a URL (e.g.: `https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/icons/geo-alt.svg`).
     *
     * @param non-empty-string $url
     */
    public static function url(string $url): UrlIcon
    {
        return new UrlIcon($url);
    }

    /**
     * Creates a new icon based on an SVG string (e.g.: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">...</svg>`).
     * Using an SVG string may not be the best option if you want to customize the icon afterward,
     * it would be preferable to use {@see Icon::ux()} or {@see Icon::url()} instead.
     *
     * @param non-empty-string $html
     */
    public static function svg(string $html): SvgIcon
    {
        return new SvgIcon($html);
    }

    /**
     * Creates a new icon based on a UX icon name (e.g.: `fa:map-marker`).
     *
     * @param non-empty-string $name
     */
    public static function ux(string $name): UxIcon
    {
        return new UxIcon($name);
    }

    /**
     * @param positive-int $width
     * @param positive-int $height
     */
    protected function __construct(
        protected IconType $type,
        protected int $width = 24,
        protected int $height = 24,
    ) {
    }

    /**
     * Sets the width of the icon.
     *
     * @param positive-int $width
     */
    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Sets the height of the icon.
     *
     * @param positive-int $height
     */
    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * @param array{ type: value-of<IconType>, width: positive-int, height: positive-int }
     *     &(array{ url: non-empty-string }
     *      |array{ html: non-empty-string }
     *      |array{ name: non-empty-string }) $data
     *
     * @internal
     */
    public static function fromArray(array $data): static
    {
        return match ($data['type']) {
            IconType::Url->value => UrlIcon::fromArray($data),
            IconType::Svg->value => SvgIcon::fromArray($data),
            IconType::UxIcon->value => UxIcon::fromArray($data),
            default => throw new InvalidArgumentException(\sprintf('Invalid icon type %s.', $data['type'])),
        };
    }
}
