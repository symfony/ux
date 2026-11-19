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

final class Side
{
    public function __construct(
        public readonly string $label,
        public readonly string $projectDir,
        public readonly string $pagesDir,
        public readonly int $port,
        public readonly string $sha,
        public readonly bool $dirty,
        public readonly VendorVersions $versions,
        public readonly bool $isWorkingTree,
    ) {
    }

    public static function slug(string $label): string
    {
        return (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $label);
    }

    public function baseUrl(): string
    {
        return \sprintf('http://127.0.0.1:%d', $this->port);
    }

    public function logFile(string $mode): string
    {
        if ($this->isWorkingTree) {
            return \sprintf('frankenphp-%s.log', $mode);
        }

        return \sprintf('frankenphp-%s-%s.log', $mode, self::slug($this->label));
    }
}
