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
 * Represents an inline SVG icon.
 *
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
class SvgIcon extends Icon
{
    /**
     * @param non-empty-string $html
     */
    protected function __construct(
        protected string $html,
    ) {
        parent::__construct(IconType::Svg);
    }

    /**
     * @param array{ html: string } $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            html: $data['html'],
        );
    }

    /**
     * @throws \LogicException the SvgIcon can not be customized
     */
    public function width(int $width): never
    {
        throw new \LogicException('Unable to configure the SvgIcon width, please configure it in the HTML with the "width" attribute on the root element instead.');
    }

    /**
     * @throws \LogicException the SvgIcon can not be customized
     */
    public function height(int $height): never
    {
        throw new \LogicException('Unable to configure the SvgIcon height, please configure it in the HTML with the "height" attribute on the root element instead.');
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'html' => $this->html,
        ];
    }
}
