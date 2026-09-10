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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\UX\Disclose\Checksum\ChecksumCalculator;

/**
 * Signs a disclose context and restores it from a request.
 *
 * The signed payload carries an expiry timestamp so a reference captured from
 * a page, a proxy log or a browser history cannot be replayed indefinitely:
 * the signature covers both the context and its expiry, and {@see fromRequest()}
 * rejects any context whose expiry is in the past.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DiscloseContextSigner
{
    public function __construct(
        private readonly ChecksumCalculator $checksumCalculator,
        private readonly ?int $ttl = null,
    ) {}

    /**
     * @return array{d: string, h: string}
     */
    public function sign(DiscloseContext $context, ?int $expiresAt = null): array
    {
        $envelope = ['payload' => $context->toArray()];

        $expiry = $expiresAt ?? (null !== $this->ttl ? time() + $this->ttl : null);
        if (null !== $expiry) {
            $envelope['exp'] = $expiry;
        }

        return [
            'd' => $this->encodePayload($envelope),
            'h' => $this->checksumCalculator->calculateForArray($envelope),
        ];
    }

    public function fromRequest(Request $request): DiscloseContext
    {
        $data = $request->query->get('d');
        $checksum = $request->query->get('h');

        if (!\is_string($data) || !\is_string($checksum)) {
            throw new BadRequestHttpException('The disclose context is missing.');
        }

        $envelope = $this->decodePayload($data);
        if (null === $envelope || !isset($envelope['payload']) || !\is_array($envelope['payload'])) {
            throw new BadRequestHttpException('The disclose context is invalid.');
        }

        if (!hash_equals($this->checksumCalculator->calculateForArray($envelope), $checksum)) {
            throw new BadRequestHttpException('The disclose context has been tampered with.');
        }

        $expiry = $envelope['exp'] ?? null;
        if (null !== $expiry && time() > (int) $expiry) {
            throw new ExpiredDiscloseContextException('The disclose context has expired.');
        }

        return DiscloseContext::fromArray($envelope['payload']);
    }

    private function encodePayload(array $payload): string
    {
        return rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    }

    private function decodePayload(string $data): ?array
    {
        $json = base64_decode(strtr($data, '-_', '+/'), true);
        if (false === $json) {
            return null;
        }

        $payload = json_decode($json, true);
        if (!\is_array($payload)) {
            return null;
        }

        return $payload;
    }
}
