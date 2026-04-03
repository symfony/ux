<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content;

final class BlockContent extends EditorContent
{
    /**
     * @param list<array{type: string, data: array, id?: string}> $blocks
     */
    public function __construct(
        public readonly array $blocks,
        public readonly string $schemaVersion = '1.0',
        array $metadata = [],
    ) {
        parent::__construct(EditorContentFormat::Blocks, $metadata);
    }

    public function getRaw(): array
    {
        return $this->blocks;
    }

    public function isEmpty(): bool
    {
        return $this->blocks === [];
    }

    public function filterByType(string $type): self
    {
        return new self(
            array_values(array_filter($this->blocks, fn (array $b): bool => ($b['type'] ?? null) === $type)),
            $this->schemaVersion,
            $this->metadata,
        );
    }

    public static function fromArray(array $payload, array $metadata = []): self
    {
        return new self(
            $payload['blocks'] ?? [],
            (string) ($payload['version'] ?? '1.0'),
            $metadata,
        );
    }
}
