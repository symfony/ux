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

final class ApacheBench
{
    public function parse(string $report, int $expectedStatus): Measurement
    {
        if (!preg_match('/^Requests per second:\s+([\d.]+)/m', $report, $rps)) {
            throw new \RuntimeException('Could not parse the ab report.');
        }

        $errors = $this->count('/^Failed requests:\s+(\d+)/m', $report);
        if ($expectedStatus >= 200 && $expectedStatus < 300) {
            $errors += $this->count('/^Non-2xx responses:\s+(\d+)/m', $report);
        }

        return new Measurement(
            (float) $rps[1],
            $this->percentile(50, $report),
            $this->percentile(95, $report),
            $this->percentile(99, $report),
            $errors,
        );
    }

    /**
     * @param array<string, string> $headers
     *
     * @return list<string>
     */
    public function command(string $url, int $concurrency, ?int $seconds, ?int $requests, ?string $bodyFile = null, array $headers = []): array
    {
        if (null === $seconds && null === $requests) {
            throw new \InvalidArgumentException('Either $seconds or $requests is required.');
        }

        $command = ['ab', '-q', '-k', '-c', (string) $concurrency];

        if (null !== $seconds) {
            array_push($command, '-t', (string) $seconds, '-n', '50000000');
        } else {
            array_push($command, '-n', (string) $requests);
        }

        if (null !== $bodyFile) {
            array_push($command, '-p', $bodyFile, '-T', 'application/x-www-form-urlencoded');
        }

        foreach ($headers as $name => $value) {
            array_push($command, '-H', \sprintf('%s: %s', $name, $value));
        }

        $command[] = $url;

        return $command;
    }

    /**
     * @param list<string> $command
     */
    public function run(array $command): string
    {
        $process = new Process($command);
        $process->setTimeout(null);
        $process->mustRun();

        return $process->getOutput();
    }

    private function count(string $pattern, string $report): int
    {
        return preg_match($pattern, $report, $matches) ? (int) $matches[1] : 0;
    }

    private function percentile(int $percent, string $report): float
    {
        if (!preg_match(\sprintf('/^\s*%d%%\s+(\d+)/m', $percent), $report, $matches)) {
            throw new \RuntimeException('Could not parse the ab report.');
        }

        return (float) $matches[1];
    }
}
