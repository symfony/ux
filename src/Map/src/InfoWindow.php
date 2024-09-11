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
final readonly class InfoWindow
{
    public static function fromArray(
        ?string $headerContent = null,
        ?string $content = null,
        ?array $position = null,
        bool $opened = false,
        bool $autoClose = true,
        array $extra = [],
    ): self {
        return new self(
            $headerContent,
            $content,
            $position ? Point::fromArray($position) : null,
            $opened,
            $autoClose,
            $extra,
        );
    }

    /**
     * @param array<string, mixed> $extra Extra data, can be used by the developer to store additional information and use them later JavaScript side
     */
    public function __construct(
        private ?string $headerContent = null,
        private ?string $content = null,
        private ?Point $position = null,
        private bool $opened = false,
        private bool $autoClose = true,
        private array $extra = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'headerContent' => $this->headerContent,
            'content' => $this->content,
            'position' => $this->position?->toArray(),
            'opened' => $this->opened,
            'autoClose' => $this->autoClose,
            'extra' => (object) $this->extra,
        ];
    }
}
