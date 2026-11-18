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

final class ResultsWriter
{
    public function __construct(
        private readonly TargetProvider $provider,
    ) {
    }

    /**
     * @param list<TargetStats> $targets
     * @param list<Round>       $rounds
     */
    public function write(RunInfo $run, array $targets, array $rounds): string
    {
        $lines = $this->header($run);

        foreach ($this->provider->packages() as $package) {
            $packageTargets = array_values(array_filter(
                $targets,
                static fn (TargetStats $stats): bool => $package === $stats->target->package,
            ));
            $packageRounds = $this->packageRounds($package, $rounds);
            if ([] === $packageTargets && [] === $packageRounds) {
                continue;
            }

            $lines[] = \sprintf('## %s', $package);
            $lines[] = '';

            if ([] !== $packageTargets) {
                array_push($lines, ...$this->targetsTable($package, $packageTargets));
                $lines[] = '';
            }

            if ([] !== $packageRounds) {
                array_push($lines, ...$this->resultsTable($packageRounds));
                $lines[] = '';
            }

            if ('Css' === $package) {
                $sentences = $this->cssSentences($rounds);
                if ([] !== $sentences) {
                    array_push($lines, ...$sentences);
                    $lines[] = '';
                }
            }
        }

        return MarkdownTables::align(implode("\n", $lines));
    }

    /**
     * @return list<string>
     */
    private function header(RunInfo $run): array
    {
        $sha = $run->dirty ? \sprintf('%s with uncommitted changes', $run->sha) : $run->sha;

        return [
            '# Results',
            '',
            \sprintf('Written by `%s` on %s, from symfony/ux %s.', $run->command, $run->date, $sha),
            '',
            \sprintf('- %s, static binary, opcache and preloading on (see `frankenphp.ini`)', $run->frankenPhpVersion),
            \sprintf('- Symfony %s, Twig %s, tales-from-a-dev/twig-tailwind-extra %s, `prod` environment', $run->symfonyVersion, $run->twigVersion, $run->tailwindExtraVersion),
            \sprintf('- %s, %d cores, %s; `ab` runs on the same machine', $run->cpu, $run->cores, $run->os),
            \sprintf('- `ab -k -c %d`: %d warmup requests per target, then %s of %d seconds per target', $run->concurrency, $run->warmup, $this->rounds($run->rounds), $run->seconds),
            \sprintf('- Load average: %.2f before the run, %.2f after (1 minute)', $run->loadBefore, $run->loadAfter),
            '',
        ];
    }

    private function rounds(int $rounds): string
    {
        return 1 === $rounds ? '1 round' : \sprintf('%d rounds', $rounds);
    }

    /**
     * @param list<TargetStats> $targets
     *
     * @return list<string>
     */
    private function targetsTable(string $package, array $targets): array
    {
        if ('Css' === $package) {
            $lines = ['| Page | HTML | `class` attributes | Classes | Unique classes |', '| --- | --- | --- | --- | --- |'];
            foreach ($targets as $item) {
                $lines[] = \sprintf(
                    '| %s | %s | %d | %d | %d |',
                    $item->target->label(),
                    $this->kilobytes($item->stats),
                    $item->stats->classAttributes(),
                    $item->stats->classes(),
                    $item->stats->uniqueClasses(),
                );
            }

            return $lines;
        }

        $lines = ['| Target | HTML | Holds |', '| --- | --- | --- |'];
        foreach ($targets as $item) {
            $lines[] = \sprintf('| %s | %s | %s |', $item->target->label(), $this->kilobytes($item->stats), $item->stats->holds());
        }

        return $lines;
    }

    private function kilobytes(PageStats $stats): string
    {
        return \sprintf('%.1f KB', $stats->bytes() / 1024);
    }

    /**
     * @param list<Round> $rounds
     *
     * @return list<array{Target, list<Round>}> rounds grouped by target, in provider order
     */
    private function packageRounds(string $package, array $rounds): array
    {
        $grouped = [];
        foreach ($this->provider->targets() as $target) {
            if ($package !== $target->package) {
                continue;
            }

            $targetRounds = array_values(array_filter(
                $rounds,
                static fn (Round $round): bool => $target->name === $round->target,
            ));
            if ([] !== $targetRounds) {
                $grouped[] = [$target, $targetRounds];
            }
        }

        return $grouped;
    }

    /**
     * @param list<array{Target, list<Round>}> $packageRounds
     *
     * @return list<string>
     */
    private function resultsTable(array $packageRounds): array
    {
        $lines = [
            '| Mode | Target | req/s (median) | req/s (range) | p50 | p95 | p99 | Errors |',
            '| --- | --- | --- | --- | --- | --- | --- | --- |',
        ];

        foreach (FrankenPhpServer::MODES as $mode) {
            foreach ($packageRounds as [$target, $targetRounds]) {
                $modeRounds = $this->forMode($targetRounds, $mode);
                if ([] === $modeRounds) {
                    continue;
                }

                $rates = array_map(static fn (Round $round): float => $round->measurement->requestsPerSecond, $modeRounds);
                $lines[] = \sprintf(
                    '| %s | %s | %.1f | %.1f-%.1f | %g ms | %g ms | %g ms | %d |',
                    $mode,
                    $target->label(),
                    $this->median($rates),
                    min($rates),
                    max($rates),
                    $this->median(array_map(static fn (Round $round): float => $round->measurement->p50, $modeRounds)),
                    $this->median(array_map(static fn (Round $round): float => $round->measurement->p95, $modeRounds)),
                    $this->median(array_map(static fn (Round $round): float => $round->measurement->p99, $modeRounds)),
                    array_sum(array_map(static fn (Round $round): int => $round->measurement->errors, $modeRounds)),
                );
            }
        }

        return $lines;
    }

    /**
     * @param list<Round> $rounds
     *
     * @return list<string>
     */
    private function cssSentences(array $rounds): array
    {
        $sentences = [];
        foreach (FrankenPhpServer::MODES as $mode) {
            $tailwind = $this->rates($rounds, 'css/tailwind', $mode);
            $uxCss = $this->rates($rounds, 'css/ux-css', $mode);
            if ([] === $tailwind || [] === $uxCss) {
                continue;
            }

            $difference = ($this->median($uxCss) / $this->median($tailwind) - 1) * 100;
            $spread = max($this->spread($tailwind), $this->spread($uxCss));
            $sentences[] = \sprintf(
                '- %s mode: UX CSS serves %+.1f%% requests per second compared to Tailwind CSS; the rounds of a page spread over up to %.1f%%.',
                $mode,
                $difference,
                $spread,
            );
        }

        return $sentences;
    }

    /**
     * @param list<Round> $rounds
     *
     * @return list<float>
     */
    private function rates(array $rounds, string $target, string $mode): array
    {
        $targetRounds = array_filter($rounds, static fn (Round $round): bool => $target === $round->target);

        return array_map(
            static fn (Round $round): float => $round->measurement->requestsPerSecond,
            $this->forMode(array_values($targetRounds), $mode),
        );
    }

    /**
     * @param list<Round> $rounds
     *
     * @return list<Round>
     */
    private function forMode(array $rounds, string $mode): array
    {
        return array_values(array_filter($rounds, static fn (Round $round): bool => $mode === $round->mode));
    }

    /**
     * @param list<float> $rates
     */
    private function spread(array $rates): float
    {
        return (max($rates) - min($rates)) / $this->median($rates) * 100;
    }

    /**
     * @param list<float> $values
     */
    private function median(array $values): float
    {
        sort($values);
        $count = \count($values);
        $middle = intdiv($count, 2);

        return 0 === $count % 2 ? ($values[$middle - 1] + $values[$middle]) / 2 : $values[$middle];
    }
}
