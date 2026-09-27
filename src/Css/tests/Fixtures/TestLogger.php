<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Fixtures;

use Psr\Log\AbstractLogger;

final class TestLogger extends AbstractLogger
{
    /**
     * @var list<array{mixed, string, array<array-key, mixed>}>
     */
    private array $logs = [];

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [$level, (string) $message, $context];
    }

    /**
     * @return list<array{mixed, string, array<array-key, mixed>}>
     */
    public function cleanLogs(): array
    {
        $logs = $this->logs;
        $this->logs = [];

        return $logs;
    }
}
