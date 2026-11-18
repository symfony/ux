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

    private ?Process $process = null;

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

    public function start(string $mode, int $port): void
    {
        $logFile = \sprintf('%s/var/log/frankenphp-%s.log', $this->projectDir, $mode);
        $this->truncate($logFile);

        $process = new Process(
            [$this->binary, 'run', '--config', 'Caddyfile'],
            $this->projectDir,
            [
                'FRANKENPHP_CONFIG' => 'worker' === $mode ? 'worker ./public/index.php' : '',
                'APP_ENV' => 'prod',
                'APP_DEBUG' => '0',
                'PHPRC' => \sprintf('%s/frankenphp.ini', $this->projectDir),
                'PORT' => (string) $port,
            ],
        );
        $process->setTimeout(null);
        $process->start(static function (string $type, string $buffer) use ($logFile): void {
            file_put_contents($logFile, $buffer, \FILE_APPEND);
        });
        $this->process = $process;

        if ($this->waitUntilReady($process, $port)) {
            return;
        }

        $this->stop();

        throw new \RuntimeException(\sprintf('FrankenPHP did not start in "%s" mode, see var/log/frankenphp-%s.log.', $mode, $mode));
    }

    public function stop(): void
    {
        $this->process?->stop();
        $this->process = null;
    }

    public function isRunning(): bool
    {
        return $this->process?->isRunning() ?? false;
    }

    /**
     * @param list<string> $args
     */
    public function phpCli(array $args): void
    {
        $process = new Process(
            [$this->binary, 'php-cli', ...$args],
            $this->projectDir,
            ['APP_ENV' => 'prod', 'APP_DEBUG' => '0'],
        );
        $process->setTimeout(null);
        $process->mustRun();
    }

    public function baseUrl(int $port): string
    {
        return \sprintf('http://127.0.0.1:%d', $port);
    }

    private function truncate(string $logFile): void
    {
        $directory = \dirname($logFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        file_put_contents($logFile, '');
    }

    private function waitUntilReady(Process $process, int $port): bool
    {
        $url = \sprintf('%s/css/ux-css', $this->baseUrl($port));

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
