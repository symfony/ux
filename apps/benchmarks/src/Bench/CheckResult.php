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

final class CheckResult
{
    /**
     * @param list<TargetStats>     $stats
     * @param array<string, string> $unavailable target name => reason
     * @param array<string, string> $pages       path => html
     */
    public function __construct(
        private readonly string $directory,
        public readonly array $stats,
        public readonly array $unavailable,
        private readonly array $pages,
    ) {
    }

    public static function file(string $directory, Target $target, string $extension): string
    {
        return \sprintf('%s/%s.%s', $directory, str_replace('/', '-', $target->name), $extension);
    }

    public function isAvailable(Target $target): bool
    {
        return !isset($this->unavailable[$target->name]);
    }

    public function page(string $path): string
    {
        return $this->pages[$path] ?? throw new \LogicException(\sprintf('The page "%s" was not checked.', $path));
    }

    public function bodyFile(Target $target): string
    {
        return self::file($this->directory, $target, 'body');
    }
}
