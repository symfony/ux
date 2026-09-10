<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Audit;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\UX\Disclose\Context\DiscloseContext;

/**
 * Writes one structured record per disclosure outcome.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DiscloseAuditLogger
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function log(DiscloseContext $context, DiscloseStatus $status, string $identity, array $extra = []): void
    {
        $this->logger->log($this->logLevel($status), \sprintf('Protected value disclosure: %s', $status->value), [
            'identity' => $identity,
            'context' => [
                'class' => $context->class,
                'id' => $context->id,
                'field' => $context->field,
            ],
            ...$extra,
        ]);
    }

    private function logLevel(DiscloseStatus $status): string
    {
        return match ($status) {
            DiscloseStatus::Attempt, DiscloseStatus::Success => LogLevel::INFO,
            DiscloseStatus::AuthDenied, DiscloseStatus::RateLimited => LogLevel::WARNING,
        };
    }
}
