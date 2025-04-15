<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map;

/**
 * Represents an information window that can be displayed on a map.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class InfoWindow
{
    /**
     * @param array<string, mixed> $extra Extra data, can be used by the developer to store additional information and
     *                                    use them later JavaScript side
     */
    public function __construct(
        private readonly ?string $headerContent = null,
        private readonly ?string $content = null,
        private readonly ?Point $position = null,
        private readonly bool $opened = false,
        private readonly bool $autoClose = true,
        private readonly array $extra = [],
    ) {
    }

    /**
     * @return array{
     *     headerContent: string|null,
     *     content: string|null,
     *     position: array{lat: float, lng: float}|null,
     *     opened: bool,
     *     autoClose: bool,
     *     extra: object,
     * }
     */
    public function toArray(): array
    {
        return [
            'headerContent' => $this->headerContent,
            'content' => $this->content,
            'position' => $this->position?->toArray(),
            'opened' => $this->opened,
            'autoClose' => $this->autoClose,
            'extra' => $this->extra,
        ];
    }

    /**
     * @param array{
     *     headerContent: string|null,
     *     content: string|null,
     *     position: array{lat: float, lng: float}|null,
     *     opened: bool,
     *     autoClose: bool,
     *     extra: array,
     * } $data
     *
     * @internal
     */
    public static function fromArray(array $data): self
    {
        if (isset($data['position'])) {
            $data['position'] = Point::fromArray($data['position']);
        }

        return new self(...$data);
    }
}
