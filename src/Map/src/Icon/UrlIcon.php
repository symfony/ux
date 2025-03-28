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

/**
 * Represents an URL icon.
 *
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
class UrlIcon extends Icon
{
    /**
     * @param non-empty-string $url
     * @param positive-int     $width
     * @param positive-int     $height
     */
    protected function __construct(
        protected string $url,
        int $width = 24,
        int $height = 24,
    ) {
        parent::__construct(IconType::Url, $width, $height);
    }

    public static function fromArray(array $data): static
    {
        return new self(
            url: $data['url'],
            width: $data['width'],
            height: $data['height'],
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'url' => $this->url,
        ];
    }
}
