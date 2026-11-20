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
     * @param list<Side>                           $sides
     * @param list<TargetStats>                    $targets
     * @param list<Round>                          $rounds
     * @param array<string, array<string, string>> $unavailable
     */
    public function write(RunInfo $run, array $sides, array $targets, array $rounds, array $unavailable): string
    {
        $lines = $this->header($run, $sides);
        $packagesByTarget = array_column($this->provider->targets(), 'package', 'name');

        foreach ($this->provider->packages() as $package) {
            $packageTargets = array_values(array_filter(
                $targets,
                static fn (TargetStats $stats): bool => $package === $stats->target->package,
            ));
            $packageRounds = array_values(array_filter(
                $rounds,
                static fn (Round $round): bool => $package === ($packagesByTarget[$round->target] ?? null),
            ));
            if ([] === $packageTargets && [] === $packageRounds) {
                continue;
            }

            $lines[] = \sprintf('## %s', $package);
            $lines[] = '';

            if ([] !== $packageTargets) {
                array_push($lines, ...$this->targetsTable($package, $packageTargets));
                $lines[] = '';
            }

            foreach (FrankenPhpServer::MODES as $mode) {
                $modeTable = $this->modeTable($package, $mode, $sides, $packageRounds);
                if ([] !== $modeTable) {
                    array_push($lines, \sprintf('**%s mode**', $mode), '');
                    array_push($lines, ...$modeTable);
                    $lines[] = '';
                }
            }

            if ('Css' === $package) {
                $sentences = $this->cssSentences($sides, $rounds);
                if ([] !== $sentences) {
                    array_push($lines, ...$sentences);
                    $lines[] = '';
                }
            }
        }

        if ([] !== $unavailable) {
            array_push($lines, ...$this->unavailableSection($unavailable));
        }

        return MarkdownTables::align(implode("\n", $lines));
    }

    /**
     * @param list<Side> $sides
     *
     * @return list<string>
     */
    private function header(RunInfo $run, array $sides): array
    {
        $lines = [
            '# Results',
            '',
            \sprintf('Written by `%s` on %s.', $run->command, $run->date),
            '',
        ];

        foreach ($sides as $side) {
            $lines[] = \sprintf(
                '- **%s**: symfony/ux %s, Symfony %s, Twig %s, tales-from-a-dev/twig-tailwind-extra %s',
                $side->label,
                $side->dirty ? \sprintf('%s with uncommitted changes', $side->sha) : $side->sha,
                $side->versions->symfony,
                $side->versions->twig,
                $side->versions->tailwindExtra,
            );
        }

        return [
            ...$lines,
            \sprintf('- %s, static binary, opcache and preloading on (see `frankenphp.ini`), `prod` environment', $run->frankenPhpVersion),
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
     * @param list<Side>  $sides
     * @param list<Round> $packageRounds
     *
     * @return list<string>
     */
    private function modeTable(string $package, string $mode, array $sides, array $packageRounds): array
    {
        $compared = array_values(array_filter($sides, static fn (Side $side): bool => !$side->isWorkingTree));
        $workingTree = array_find($sides, static fn (Side $side): bool => $side->isWorkingTree);

        $header = '| Target |';
        $separator = '| --- |';
        foreach ($sides as $side) {
            foreach (['req/s', 'p50', 'p95', 'p99'] as $column) {
                $header .= \sprintf(' %s %s |', $side->label, $column);
                $separator .= ' --- |';
            }
        }
        foreach ($compared as $side) {
            $header .= \sprintf(' Δ %s |', $side->label);
            $separator .= ' --- |';
        }
        $header .= ' Errors |';
        $separator .= ' --- |';

        $lines = [];
        foreach ($this->provider->targets() as $target) {
            if ($package !== $target->package) {
                continue;
            }

            $cells = [];
            foreach ($sides as $side) {
                $cells[$side->label] = $this->roundsFor($side->label, $mode, $target->name, $packageRounds);
            }
            if ([] === array_filter($cells)) {
                continue;
            }

            $row = \sprintf('| %s |', $target->label());
            $errors = [];
            foreach ($sides as $side) {
                $sideRounds = $cells[$side->label];
                if ([] === $sideRounds) {
                    $row .= ' n/a | n/a | n/a | n/a |';
                    $errors[] = '-';
                    continue;
                }

                $rates = $this->rates($sideRounds);
                $row .= \sprintf(
                    ' %.1f (%.1f-%.1f) | %g ms | %g ms | %g ms |',
                    $this->median($rates),
                    min($rates),
                    max($rates),
                    $this->median(array_map(static fn (Round $round): float => $round->measurement->p50, $sideRounds)),
                    $this->median(array_map(static fn (Round $round): float => $round->measurement->p95, $sideRounds)),
                    $this->median(array_map(static fn (Round $round): float => $round->measurement->p99, $sideRounds)),
                );
                $errors[] = (string) array_sum(array_map(static fn (Round $round): int => $round->measurement->errors, $sideRounds));
            }

            foreach ($compared as $side) {
                $row .= \sprintf(' %s |', $this->delta($workingTree, $side, $cells));
            }

            $lines[] = \sprintf('%s %s |', $row, implode(' / ', $errors));
        }

        return [] === $lines ? [] : [$header, $separator, ...$lines];
    }

    /**
     * @param array<string, list<Round>> $cells
     */
    private function delta(?Side $workingTree, Side $side, array $cells): string
    {
        $current = null === $workingTree ? [] : $cells[$workingTree->label];
        $previous = $cells[$side->label];
        if ([] === $current || [] === $previous) {
            return '';
        }

        $previousMedian = $this->median($this->rates($previous));
        if (0.0 === $previousMedian) {
            return '';
        }

        return \sprintf('%+.1f%%', ($this->median($this->rates($current)) / $previousMedian - 1) * 100);
    }

    /**
     * @param list<Round> $rounds
     *
     * @return list<Round>
     */
    private function roundsFor(string $side, string $mode, string $target, array $rounds): array
    {
        return array_values(array_filter(
            $rounds,
            static fn (Round $round): bool => $side === $round->side && $mode === $round->mode && $target === $round->target,
        ));
    }

    /**
     * @param list<Side>  $sides
     * @param list<Round> $rounds
     *
     * @return list<string>
     */
    private function cssSentences(array $sides, array $rounds): array
    {
        $sentences = [];
        foreach ($sides as $side) {
            foreach (FrankenPhpServer::MODES as $mode) {
                $tailwind = $this->rates($this->roundsFor($side->label, $mode, 'css/tailwind', $rounds));
                $uxCss = $this->rates($this->roundsFor($side->label, $mode, 'css/ux-css', $rounds));
                if ([] === $tailwind || [] === $uxCss) {
                    continue;
                }

                $sentences[] = \sprintf(
                    '- %s, %s mode: UX CSS serves %+.1f%% requests per second compared to Tailwind CSS; the rounds of a page spread over up to %.1f%%.',
                    $side->label,
                    $mode,
                    ($this->median($uxCss) / $this->median($tailwind) - 1) * 100,
                    max($this->spread($tailwind), $this->spread($uxCss)),
                );
            }
        }

        return $sentences;
    }

    /**
     * @param array<string, array<string, string>> $unavailable
     *
     * @return list<string>
     */
    private function unavailableSection(array $unavailable): array
    {
        $lines = ['## Not available', ''];
        foreach ($unavailable as $side => $reasons) {
            foreach ($reasons as $target => $reason) {
                $lines[] = \sprintf('- %s, `%s`: %s', $side, $target, $reason);
            }
        }
        $lines[] = '';

        return $lines;
    }

    /**
     * @param list<Round> $rounds
     *
     * @return list<float>
     */
    private function rates(array $rounds): array
    {
        return array_map(static fn (Round $round): float => $round->measurement->requestsPerSecond, $rounds);
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
