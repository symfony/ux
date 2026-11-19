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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FrankenPhpServer
{
    public const MODES = ['classic', 'worker'];

    private const POLL_ATTEMPTS = 100;
    private const POLL_DELAY_MICROSECONDS = 200_000;

    /** @var array<string, Process> */
    private array $processes = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/frankenphp/frankenphp')]
        private readonly string $binary,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly HttpClientInterface $client,
    ) {
    }

    public function exists(): bool
    {
        return is_executable($this->binary);
    }

    public function version(): string
    {
        $process = new Process([$this->binary, 'version']);
        $process->mustRun();
        $words = explode(' ', trim($process->getOutput()));

        return \sprintf('%s %s with %s %s', $words[0], $words[1], $words[2], $words[3]);
    }

    public function start(Side $side, string $mode): void
    {
        $logFile = \sprintf('%s/var/log/%s', $this->projectDir, $side->logFile($mode));
        $this->truncate($logFile);

        $process = new Process(
            [$this->binary, 'run', '--config', 'Caddyfile'],
            $side->projectDir,
            [
                'FRANKENPHP_CONFIG' => 'worker' === $mode ? 'worker ./public/index.php' : '',
                'APP_ENV' => 'prod',
                'APP_DEBUG' => '0',
                'PHPRC' => \sprintf('%s/frankenphp.ini', $side->projectDir),
                'PORT' => (string) $side->port,
            ],
        );
        $process->setTimeout(null);
        $process->start(static function (string $type, string $buffer) use ($logFile): void {
            file_put_contents($logFile, $buffer, \FILE_APPEND);
        });
        $this->processes[$side->label] = $process;

        if ($this->waitUntilReady($process, $side)) {
            return;
        }

        $this->stop($side);

        throw new \RuntimeException(\sprintf('FrankenPHP did not start for "%s" in "%s" mode, see var/log/%s.', $side->label, $mode, $side->logFile($mode)));
    }

    public function stop(Side $side): void
    {
        ($this->processes[$side->label] ?? null)?->stop();
        unset($this->processes[$side->label]);
    }

    public function stopAll(): void
    {
        foreach ($this->processes as $process) {
            $process->stop();
        }

        $this->processes = [];
    }

    /**
     * @param list<string> $args
     */
    public function phpCli(array $args, string $projectDir): void
    {
        $process = new Process(
            [$this->binary, 'php-cli', ...$args],
            $projectDir,
            ['APP_ENV' => 'prod', 'APP_DEBUG' => '0'],
        );
        $process->setTimeout(null);
        $process->mustRun();
    }

    private function truncate(string $logFile): void
    {
        $directory = \dirname($logFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        file_put_contents($logFile, '');
    }

    private function waitUntilReady(Process $process, Side $side): bool
    {
        $url = \sprintf('%s/', $side->baseUrl());

        for ($attempt = 0; $attempt < self::POLL_ATTEMPTS; ++$attempt) {
            if (!$process->isRunning()) {
                return false;
            }

            try {
                if (200 === $this->client->request('GET', $url)->getStatusCode()) {
                    return true;
                }
            } catch (TransportExceptionInterface) {
            }

            usleep(self::POLL_DELAY_MICROSECONDS);
        }

        return false;
    }
}
