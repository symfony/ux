<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Context;

/**
 * Describes the subject to disclose (an entity, a document, or any object a
 * subject resolver understands), the field to read, and an optional
 * application-specific payload.
 *
 * A context is serialized and HMAC-signed before it is exposed to the client,
 * so it cannot be tampered with to target other records.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DiscloseContext
{
    private const ALLOWED_KEYS = ['class', 'id', 'field', 'extra'];

    private function __construct(
        public readonly string $class,
        public readonly string|int|array $id,
        public readonly ?string $field,
        private readonly array $extra,
    ) {}

    public static function create(string $class, string|int|array $id, ?string $field = null, array $extra = []): self
    {
        return new self($class, $id, $field, $extra);
    }

    public static function fromArray(array $data): self
    {
        $unknownKeys = array_diff(array_keys($data), self::ALLOWED_KEYS);
        if ($unknownKeys) {
            throw new \InvalidArgumentException(\sprintf('The disclose context contains unsupported keys: %s.', implode(', ', $unknownKeys)));
        }

        if (!isset($data['class']) || !\is_string($data['class'])) {
            throw new \InvalidArgumentException('The disclose context requires a string "class".');
        }

        $id = $data['id'] ?? null;
        if (!\is_string($id) && !\is_int($id)) {
            if (!\is_array($id) || !$id) {
                throw new \InvalidArgumentException('The disclose context requires a string, integer or non-empty array "id".');
            }

            foreach ($id as $key => $value) {
                if (!\is_string($key) && !\is_int($key)) {
                    throw new \InvalidArgumentException('The disclose context composite "id" keys must be strings or integers.');
                }

                if (!\is_string($value) && !\is_int($value)) {
                    throw new \InvalidArgumentException('The disclose context composite "id" values must be strings or integers.');
                }
            }
        }

        $field = $data['field'] ?? null;
        if (null !== $field && !\is_string($field)) {
            throw new \InvalidArgumentException('The disclose context "field" must be a string or null.');
        }

        $extra = $data['extra'] ?? [];
        if (!\is_array($extra)) {
            throw new \InvalidArgumentException('The disclose context "extra" must be an array.');
        }

        return new self($data['class'], $id, $field, $extra);
    }

    /**
     * @return array{class: string, id: string|int|array, field: ?string, extra: array}
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'id' => $this->id,
            'field' => $this->field,
            'extra' => $this->extra,
        ];
    }

    public function withField(?string $field): self
    {
        return new self($this->class, $this->id, $field, $this->extra);
    }

    public function withExtra(array $extra): self
    {
        return new self($this->class, $this->id, $this->field, $extra);
    }

    public function hasField(): bool
    {
        return null !== $this->field;
    }

    /**
     * Returns a value of the application-specific payload, or null.
     */
    public function get(string $key): mixed
    {
        return $this->extra[$key] ?? null;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }
}
