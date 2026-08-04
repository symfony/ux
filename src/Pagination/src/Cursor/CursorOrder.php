<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Cursor;

use Symfony\UX\Pagination\Exception\InvalidArgumentException;

/**
 * Effective order used by a cursor adapter.
 *
 * Field orders drive local queries. Opaque identities describe an order owned
 * by a remote cursor source. Both produce a stable fingerprint bound to signed
 * cursor tokens.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CursorOrder
{
    /**
     * @param non-empty-string  $fingerprintInput
     * @param list<string>|null $fields
     * @param 'ASC'|'DESC'|null $direction
     */
    private function __construct(
        private readonly string $fingerprintInput,
        private readonly ?array $fields = null,
        private readonly ?string $direction = null,
    ) {
    }

    /**
     * @param non-empty-list<string> $fields
     */
    public static function byFields(array $fields, string $direction): self
    {
        $fields = array_values($fields);
        if ([] === $fields || array_any($fields, static fn (mixed $field): bool => !\is_string($field) || '' === $field)) {
            throw new InvalidArgumentException('Cursor order fields must be non-empty strings.');
        }

        $direction = strtoupper($direction);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Cursor order direction must be "ASC" or "DESC".');
        }

        return new self(
            json_encode([
                'type' => 'fields',
                'fields' => $fields,
                'direction' => $direction,
            ], \JSON_THROW_ON_ERROR),
            $fields,
            $direction,
        );
    }

    /**
     * @param non-empty-string $identity
     */
    public static function byIdentity(string $identity): self
    {
        if ('' === $identity) {
            throw new InvalidArgumentException('Cursor order identity must not be empty.');
        }

        return new self(json_encode([
            'type' => 'opaque',
            'identity' => $identity,
        ], \JSON_THROW_ON_ERROR));
    }

    /**
     * @return list<string>|null
     */
    public function getFields(): ?array
    {
        return $this->fields;
    }

    /**
     * @return 'ASC'|'DESC'|null
     */
    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function getFingerprint(): string
    {
        return hash('sha256', $this->fingerprintInput);
    }
}
