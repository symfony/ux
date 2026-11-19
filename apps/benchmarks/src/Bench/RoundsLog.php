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

final class RoundsLog
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/bench/rounds.tsv')]
        private readonly string $path,
    ) {
    }

    public function clear(): void
    {
        file_put_contents($this->path, '');
    }

    public function append(Round $round): void
    {
        $line = \sprintf(
            "%s\t%s\t%s\t%d\t%s\t%s\t%s\t%s\t%d\n",
            $round->side,
            $round->mode,
            $round->target,
            $round->round,
            $round->measurement->requestsPerSecond,
            $round->measurement->p50,
            $round->measurement->p95,
            $round->measurement->p99,
            $round->measurement->errors,
        );

        file_put_contents($this->path, $line, \FILE_APPEND);
    }

    /**
     * @return list<Round>
     */
    public function rounds(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $rounds = [];
        foreach (file($this->path, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES) as $line) {
            $columns = explode("\t", $line);
            if (8 === \count($columns)) {
                array_unshift($columns, '');
            }

            [$side, $mode, $target, $round, $rps, $p50, $p95, $p99, $errors] = $columns;
            $rounds[] = new Round($side, $mode, $target, (int) $round, new Measurement((float) $rps, (float) $p50, (float) $p95, (float) $p99, (int) $errors));
        }

        return $rounds;
    }
}
