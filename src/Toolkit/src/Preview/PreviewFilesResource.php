<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Preview;

use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

/**
 * Keeps the container fresh only while every generated preview file exists, so a deleted file gets generated again.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PreviewFilesResource implements SelfCheckingResourceInterface
{
    /**
     * @param list<string> $files
     */
    public function __construct(
        private readonly array $files,
    ) {
    }

    public function __toString(): string
    {
        return 'ux_toolkit.preview_files.'.implode(',', $this->files);
    }

    public function isFresh(int $timestamp): bool
    {
        foreach ($this->files as $file) {
            if (!is_file($file)) {
                return false;
            }
        }

        return true;
    }
}
