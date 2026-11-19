<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

use Symfony\Component\Process\Process;

final class IdleMachineCheck
{
    private const BUSY_CPU_THRESHOLD = 5.0;
    private const OWN_COMMANDS = ['php', 'ab', 'frankenphp'];

    /**
     * @param \Closure(): float  $loadAverage
     * @param \Closure(): string $processes
     * @param \Closure(): int    $cores
     */
    public function __construct(
        private readonly \Closure $loadAverage,
        private readonly \Closure $processes,
        private readonly \Closure $cores,
    ) {
    }

    public static function withSystemProbes(): self
    {
        return new self(
            static fn (): float => (float) (sys_getloadavg()[0] ?? 0.0),
            static fn (): string => self::output(['ps', '-Ao', 'pcpu,comm'], ['LC_ALL' => 'C']),
            static fn (): int => (int) trim(self::output(['getconf', '_NPROCESSORS_ONLN'])),
        );
    }

    public function loadAverage(): float
    {
        return ($this->loadAverage)();
    }

    /**
     * @return list<string>
     */
    public function busyProcesses(): array
    {
        $busy = [];

        foreach (explode("\n", ($this->processes)()) as $line) {
            $parts = preg_split('/\s+/', trim($line), 2);
            if (2 !== \count($parts)) {
                continue;
            }

            [$pcpu, $command] = $parts;
            $pcpu = str_replace(',', '.', $pcpu);
            if (!is_numeric($pcpu) || (float) $pcpu <= self::BUSY_CPU_THRESHOLD) {
                continue;
            }

            if (\in_array(basename($command), self::OWN_COMMANDS, true)) {
                continue;
            }

            $busy[] = \sprintf('%s%% %s', (float) $pcpu, $command);
        }

        return $busy;
    }

    public function isBusy(): bool
    {
        return $this->isLoaded() || [] !== $this->busyProcesses();
    }

    /**
     * @return list<string>
     */
    public function report(): array
    {
        $lines = [];

        if ($this->isLoaded()) {
            $lines[] = \sprintf('Load average %.2f over the last minute, more than half of the %d cores', $this->loadAverage(), ($this->cores)());
        }

        foreach ($this->busyProcesses() as $process) {
            $lines[] = \sprintf('Busy process: %s', $process);
        }

        return $lines;
    }

    private function isLoaded(): bool
    {
        return $this->loadAverage() > ($this->cores)() / 2;
    }

    /**
     * @param list<string>          $command
     * @param array<string, string> $env
     */
    private static function output(array $command, array $env = []): string
    {
        $process = new Process($command, null, $env);
        $process->mustRun();

        return $process->getOutput();
    }
}
