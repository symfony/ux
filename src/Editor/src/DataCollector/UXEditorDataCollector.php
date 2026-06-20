<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

final class UXEditorDataCollector extends DataCollector
{
    public function __construct()
    {
        $this->reset();
    }

    public function recordBridgeUse(string $bridgeId, string $format): void
    {
        $this->data['bridges'][$bridgeId][$format] = ($this->data['bridges'][$bridgeId][$format] ?? 0) + 1;
        ++$this->data['count'];
    }

    public function recordCapabilityWarning(string $message): void
    {
        $this->data['warnings'][] = $message;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        // no-op; data already aggregated via record* methods
    }

    public function reset(): void
    {
        $this->data = ['bridges' => [], 'warnings' => [], 'count' => 0];
    }

    public function getName(): string
    {
        return 'ux_editor';
    }

    /** @return array<string, array<string, int>> */
    public function getBridges(): array
    {
        return $this->data['bridges'] ?? [];
    }

    /** @return list<string> */
    public function getWarnings(): array
    {
        return $this->data['warnings'] ?? [];
    }

    public function getBridgeUseCount(): int
    {
        return $this->data['count'] ?? 0;
    }
}
