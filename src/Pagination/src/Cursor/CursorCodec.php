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
use Symfony\UX\Pagination\Exception\RuntimeException;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class CursorCodec implements CursorCodecInterface
{
    private const MAX_TOKEN_LENGTH = 4096;
    private const MAX_VALUES = 16;

    public function __construct(
        #[\SensitiveParameter]
        private readonly string $secret,
    ) {
        if ('' === $secret) {
            throw new RuntimeException('Cursor pagination requires a non-empty "ux_pagination.cursor.secret" or "kernel.secret".');
        }
    }

    public function encode(array $values, bool $pointsForward, string $orderFingerprint, string $context): string
    {
        $values = $this->validateValues($values);
        $payload = [
            'version' => 1,
            'direction' => $pointsForward ? 'next' : 'prev',
            'order' => $orderFingerprint,
            'context' => hash('sha256', $context),
            'values' => $values,
        ];
        $json = json_encode($payload, \JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $json, $this->secret);
        $token = self::base64UrlEncode(json_encode(['payload' => $payload, 'signature' => $signature], \JSON_THROW_ON_ERROR));
        if (\strlen($token) > self::MAX_TOKEN_LENGTH) {
            throw new InvalidArgumentException(\sprintf('The cursor payload is too large to encode safely (maximum token length: %d bytes). Use shorter cursor fields.', self::MAX_TOKEN_LENGTH));
        }

        return $token;
    }

    public function decode(string $cursor, string $orderFingerprint, string $context): array
    {
        if ('' === $cursor || \strlen($cursor) > self::MAX_TOKEN_LENGTH) {
            throw new InvalidArgumentException('Invalid cursor value.');
        }

        $decoded = self::base64UrlDecode($cursor);
        try {
            $data = json_decode($decoded, true, 8, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidArgumentException('Invalid cursor value.');
        }

        if (!\is_array($data) || !isset($data['payload']) || !\is_array($data['payload'])) {
            throw new InvalidArgumentException('Invalid cursor value.');
        }

        $payload = $data['payload'];
        $order = $payload['order'] ?? null;
        $payloadContext = $payload['context'] ?? null;
        if (1 !== ($payload['version'] ?? null)
            || !\in_array($payload['direction'] ?? null, ['next', 'prev'], true)
            || !\is_string($order)
            || !hash_equals($orderFingerprint, $order)
            || !\is_string($payloadContext)
            || !hash_equals(hash('sha256', $context), $payloadContext)
            || !isset($payload['values'])
            || !\is_array($payload['values'])) {
            throw new InvalidArgumentException('The cursor does not match this pagination order.');
        }

        $signature = $data['signature'] ?? null;
        $json = json_encode($payload, \JSON_THROW_ON_ERROR);
        if (!\is_string($signature) || !hash_equals(hash_hmac('sha256', $json, $this->secret), $signature)) {
            throw new InvalidArgumentException('Invalid cursor signature.');
        }

        return [
            'values' => $this->validateValues($payload['values']),
            'forward' => 'next' === $payload['direction'],
        ];
    }

    /**
     * @param array<mixed> $values
     *
     * @return list<int|string|float>
     */
    private function validateValues(array $values): array
    {
        if (\count($values) > self::MAX_VALUES) {
            throw new InvalidArgumentException(\sprintf('Cursor pagination supports at most %d ordered values.', self::MAX_VALUES));
        }

        $validated = [];
        foreach ($values as $value) {
            if (!\is_int($value) && !\is_string($value) && !\is_float($value)) {
                throw new InvalidArgumentException('Invalid cursor value.');
            }
            if (\is_float($value) && !is_finite($value)) {
                throw new InvalidArgumentException('Cursor float values must be finite.');
            }
            $validated[] = $value;
        }

        return $validated;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if (false === $decoded) {
            throw new InvalidArgumentException('Invalid cursor value.');
        }

        return $decoded;
    }
}
