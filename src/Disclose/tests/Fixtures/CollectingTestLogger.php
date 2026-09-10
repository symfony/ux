<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Fixtures;

use Psr\Log\AbstractLogger;

/**
 * Records every log message so tests can assert the audit trail.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class CollectingTestLogger extends AbstractLogger
{
    /**
     * @var list<array{level: mixed, message: string, context: mixed[]}>
     */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }

    /**
     * @return list<array{level: mixed, message: string, context: mixed[]}>
     */
    public function getRecords(): array
    {
        return $this->records;
    }
}
