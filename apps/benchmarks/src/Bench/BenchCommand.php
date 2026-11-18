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

use Composer\InstalledVersions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

#[AsCommand('app:bench', 'Benchmarks the UX packages with FrankenPHP and ab, or checks the pages with --check')]
final class BenchCommand extends Command implements SignalableCommandInterface
{
    private const WARMUP_REQUESTS = 200;
    private const INTERRUPTED = 130;

    private bool $prepared = false;
    private ?SymfonyStyle $io = null;

    public function __construct(
        private readonly TargetProvider $provider,
        private readonly Checker $checker,
        private readonly IdleMachineCheck $idle,
        private readonly ApacheBench $ab,
        private readonly RoundsLog $log,
        private readonly ResultsWriter $writer,
        private readonly FrankenPhpServer $server,
        private readonly LiveRequestBuilder $builder,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('rounds', null, InputOption::VALUE_REQUIRED, 'Rounds per mode', 3)
            ->addOption('seconds', null, InputOption::VALUE_REQUIRED, 'Seconds of load per target and round', 15)
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Concurrent requests of ab', 4)
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'FrankenPHP mode: classic, worker or both', 'both')
            ->addOption('only', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only the targets whose name starts with this prefix')
            ->addOption('yes', null, InputOption::VALUE_NONE, 'Run even if the machine is not idle')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Port of FrankenPHP', 8180)
            ->addOption('check', null, InputOption::VALUE_NONE, 'Only check the pages and the live requests of a running server')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Base URL of the running server checked by --check');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->io = $io;
        $modes = $this->modes($input->getOption('mode'));
        $prefixes = $input->getOption('only');

        foreach ($prefixes as $prefix) {
            if ([] === $this->provider->only([$prefix])) {
                $io->error(\sprintf('No target matches "%s".', $prefix));

                return Command::FAILURE;
            }
        }

        $targets = $this->provider->only($prefixes);

        if ($input->getOption('check')) {
            return $this->check($io, $input->getOption('url'), $targets);
        }

        try {
            $this->assertPreconditions();
            if (!$this->confirmIdle($input, $io)) {
                return Command::FAILURE;
            }

            $results = $this->benchmark($input, $io, $modes, $targets);
            file_put_contents(\sprintf('%s/RESULTS.md', $this->projectDir), $results);
            $output->write($results, false, OutputInterface::OUTPUT_RAW);
        } catch (\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        } finally {
            $this->restore();
        }

        return Command::SUCCESS;
    }

    public function getSubscribedSignals(): array
    {
        return [\SIGINT, \SIGTERM];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->restore();

        return self::INTERRUPTED;
    }

    /**
     * @return list<string>
     */
    private function modes(string $mode): array
    {
        if ('both' === $mode) {
            return FrankenPhpServer::MODES;
        }

        if (!\in_array($mode, FrankenPhpServer::MODES, true)) {
            throw new InvalidArgumentException(\sprintf('The mode "%s" is not one of classic, worker or both.', $mode));
        }

        return [$mode];
    }

    /**
     * @param list<Target> $targets
     */
    private function check(SymfonyStyle $io, ?string $url, array $targets): int
    {
        if (null === $url) {
            $io->error('--check needs the base URL of a running server, given with --url.');

            return Command::FAILURE;
        }

        try {
            $stats = $this->checker->check(rtrim($url, '/'), $targets, false);
        } catch (\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $statsByName = [];
        foreach ($stats as $item) {
            $statsByName[$item->target->name] = $item->stats;
        }

        foreach ($targets as $target) {
            $line = \sprintf('OK %s %d', $target->name, $target->expectedStatus);
            if (!$target->isLive()) {
                $pageStats = $statsByName[$target->name];
                $kilobytes = $pageStats->bytes() / 1024;
                $line .= \sprintf(' (%.1f KB, %s)', $kilobytes, $pageStats->holds());
            }

            $io->writeln($line);
        }

        return Command::SUCCESS;
    }

    private function assertPreconditions(): void
    {
        if (!$this->server->exists()) {
            throw new \RuntimeException('FrankenPHP is missing: run bin/install-frankenphp first.');
        }

        if (null === new ExecutableFinder()->find('ab')) {
            throw new \RuntimeException('ab (ApacheBench) is missing.');
        }
    }

    private function confirmIdle(InputInterface $input, SymfonyStyle $io): bool
    {
        if (!$this->idle->isBusy()) {
            return true;
        }

        $io->warning($this->idle->report());

        if ($input->getOption('yes') || !$input->isInteractive()) {
            return true;
        }

        return $io->confirm('The machine is not idle. Run anyway?', false);
    }

    /**
     * @param list<string> $modes
     * @param list<Target> $targets
     */
    private function benchmark(InputInterface $input, SymfonyStyle $io, array $modes, array $targets): string
    {
        $loadBefore = $this->idle->loadAverage();
        $this->prepare($io);

        $stats = [];
        foreach ($modes as $mode) {
            $stats = $this->benchmarkMode($input, $io, $mode, $targets);
        }

        $run = $this->runInfo($input, $loadBefore);

        return $this->writer->write($run, $stats, $this->log->rounds());
    }

    private function prepare(SymfonyStyle $io): void
    {
        $io->section('Preparing the app for production');

        $this->prepared = true;
        $this->composer(['dump-autoload', '--quiet', '--no-dev', '--classmap-authoritative']);
        $this->server->phpCli(['bin/console', 'cache:clear', '--quiet']);

        $directory = \sprintf('%s/var/bench', $this->projectDir);
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        $this->log->clear();
        $pageFiles = glob(\sprintf('%s/*.html', $directory)) ?: [];
        $bodyFiles = glob(\sprintf('%s/*.body', $directory)) ?: [];
        foreach ([...$pageFiles, ...$bodyFiles] as $file) {
            unlink($file);
        }
    }

    /**
     * @param list<Target> $targets
     *
     * @return list<TargetStats>
     */
    private function benchmarkMode(InputInterface $input, SymfonyStyle $io, string $mode, array $targets): array
    {
        $io->section(\sprintf('FrankenPHP in %s mode', $mode));

        $port = (int) $input->getOption('port');
        $this->server->start($mode, $port);
        $baseUrl = $this->server->baseUrl($port);

        $stats = $this->checker->check($baseUrl, $targets, true);
        $liveRequests = $this->liveRequests($targets);
        $concurrency = (int) $input->getOption('concurrency');

        $this->warmup($baseUrl, $targets, $liveRequests, $concurrency);

        $rounds = (int) $input->getOption('rounds');
        $seconds = (int) $input->getOption('seconds');
        $width = max(array_map(static fn (Target $item): int => \strlen($item->name), $targets));
        for ($round = 1; $round <= $rounds; ++$round) {
            $order = 0 === $round % 2 ? array_reverse($targets) : $targets;
            foreach ($order as $target) {
                $command = $this->load($baseUrl, $target, $liveRequests, $concurrency, $seconds, null);
                $this->round($io, $mode, $round, $target, $command, $width);
            }
        }

        $this->server->stop();

        return $stats;
    }

    /**
     * @param list<Target> $targets
     *
     * @return array<string, LiveRequest>
     */
    private function liveRequests(array $targets): array
    {
        $requests = [];
        foreach ($targets as $target) {
            if ($target->isLive()) {
                $pageHtml = $this->checker->page($target->scenario->page);
                $requests[$target->name] = $this->builder->build($pageHtml, $target->scenario);
            }
        }

        return $requests;
    }

    /**
     * @param list<Target>               $targets
     * @param array<string, LiveRequest> $liveRequests
     */
    private function warmup(string $baseUrl, array $targets, array $liveRequests, int $concurrency): void
    {
        foreach ($targets as $target) {
            $command = $this->load($baseUrl, $target, $liveRequests, $concurrency, null, self::WARMUP_REQUESTS);
            $this->ab->run($command);
        }
    }

    /**
     * @param array<string, LiveRequest> $liveRequests
     *
     * @return list<string>
     */
    private function load(string $baseUrl, Target $target, array $liveRequests, int $concurrency, ?int $seconds, ?int $requests): array
    {
        if (!$target->isLive()) {
            return $this->ab->command($baseUrl.$target->path, $concurrency, $seconds, $requests);
        }

        $request = $liveRequests[$target->name];
        $url = $baseUrl.$request->url;
        $bodyFile = $this->checker->bodyFile($target);

        return $this->ab->command($url, $concurrency, $seconds, $requests, $bodyFile, $request->headers());
    }

    /**
     * @param list<string> $command
     */
    private function round(SymfonyStyle $io, string $mode, int $round, Target $target, array $command, int $width): void
    {
        $report = $this->ab->run($command);
        $measurement = $this->ab->parse($report, $target->expectedStatus);
        $this->log->append(new Round($mode, $target->name, $round, $measurement));

        $io->writeln(\sprintf(
            '  round %d %s %8.1f req/s  p50 %g ms  p95 %g ms  p99 %g ms  errors %d',
            $round,
            str_pad($target->name, $width),
            $measurement->requestsPerSecond,
            $measurement->p50,
            $measurement->p95,
            $measurement->p99,
            $measurement->errors,
        ));
    }

    private function runInfo(InputInterface $input, float $loadBefore): RunInfo
    {
        return new RunInfo(
            $this->commandLine($input),
            \sprintf('%s UTC', gmdate('Y-m-d H:i')),
            $this->tryOutput(['git', 'rev-parse', '--short', 'HEAD']),
            $this->isDirty(),
            $this->frankenPhpVersion(),
            $this->version('symfony/framework-bundle'),
            $this->version('twig/twig'),
            $this->version('tales-from-a-dev/twig-tailwind-extra'),
            $this->cpu(),
            (int) $this->tryOutput(['getconf', '_NPROCESSORS_ONLN']),
            \sprintf('%s %s', php_uname('s'), php_uname('r')),
            (int) $input->getOption('concurrency'),
            self::WARMUP_REQUESTS,
            (int) $input->getOption('rounds'),
            (int) $input->getOption('seconds'),
            $loadBefore,
            (float) sys_getloadavg()[0],
        );
    }

    private function isDirty(): bool
    {
        $diff = $this->process([
            'git', 'diff', '--quiet', 'HEAD', '--',
            '../../src',
            ':(exclude,glob)../../src/**/composer.json',
            ':(exclude,glob)../../src/**/doc/**',
        ]);

        return 1 === $diff?->getExitCode();
    }

    private function frankenPhpVersion(): string
    {
        try {
            return $this->server->version();
        } catch (ProcessException) {
            return '';
        }
    }

    private function commandLine(InputInterface $input): string
    {
        $parts = ['bin/bench'];

        foreach (['rounds', 'seconds', 'concurrency', 'mode', 'only', 'yes', 'port'] as $name) {
            $value = $input->getOption($name);
            $default = $this->getDefinition()->getOption($name)->getDefault();
            if ($this->optionString($value) === $this->optionString($default)) {
                continue;
            }

            if (true === $value) {
                $parts[] = \sprintf('--%s', $name);

                continue;
            }

            foreach ((array) $value as $item) {
                $parts[] = \sprintf('--%s %s', $name, $item);
            }
        }

        return implode(' ', $parts);
    }

    private function optionString(mixed $value): string
    {
        return \is_array($value) ? implode(' ', $value) : (string) $value;
    }

    private function version(string $package): string
    {
        return ltrim(InstalledVersions::getPrettyVersion($package) ?? '', 'v');
    }

    private function cpu(): string
    {
        if ('Darwin' === \PHP_OS_FAMILY) {
            return $this->tryOutput(['sysctl', '-n', 'machdep.cpu.brand_string']);
        }

        if (!is_readable('/proc/cpuinfo')) {
            return php_uname('m');
        }

        preg_match('/^model name\s*:\s*(.+)$/m', (string) file_get_contents('/proc/cpuinfo'), $matches);

        return trim($matches[1] ?? php_uname('m'));
    }

    private function restore(): void
    {
        if ($this->server->isRunning()) {
            $this->server->stop();
        }

        if ($this->prepared) {
            $this->prepared = false;
            $this->restoreAutoloader();
        }
    }

    private function restoreAutoloader(): void
    {
        try {
            $this->composer(['dump-autoload', '--quiet']);
        } catch (ProcessException $exception) {
            $this->io?->warning(\sprintf('Could not restore the autoloader, run "composer dump-autoload": %s', $exception->getMessage()));
        }
    }

    /**
     * @param list<string> $args
     */
    private function composer(array $args): void
    {
        $process = new Process(['composer', ...$args], $this->projectDir);
        $process->setTimeout(null);
        $process->mustRun();
    }

    /**
     * @param list<string> $command
     */
    private function process(array $command): ?Process
    {
        $process = new Process($command, $this->projectDir);

        try {
            $process->run();
        } catch (ProcessException) {
            return null;
        }

        return $process;
    }

    /**
     * @param list<string> $command
     */
    private function tryOutput(array $command): string
    {
        $process = $this->process($command);
        if (null === $process || !$process->isSuccessful()) {
            return '';
        }

        return trim($process->getOutput());
    }
}
