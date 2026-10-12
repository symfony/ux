<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Fixtures;

use Symfony\Component\Filesystem\Filesystem;

final class TemporaryDirectory
{
    private readonly string $root;
    private readonly Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir().'/dt-tests-'.bin2hex(random_bytes(5));
        $this->filesystem->mkdir($this->root);
    }

    public function path(string $name = ''): string
    {
        return '' === $name ? $this->root : $this->root.'/'.$name;
    }

    /** @param string|array<array-key, mixed> $contents */
    public function write(string $name, string|array $contents): string
    {
        $path = $this->path($name);
        $this->filesystem->dumpFile($path, \is_array($contents) ? json_encode($contents, \JSON_THROW_ON_ERROR) : $contents);

        return $path;
    }

    public function mkdir(string $name): string
    {
        $path = $this->path($name);
        $this->filesystem->mkdir($path);

        return $path;
    }

    public function remove(): void
    {
        $this->filesystem->remove($this->root);
    }
}
