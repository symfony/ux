<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit\Lint;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class LintIssue
{
    public readonly ?string $file;

    public function __construct(
        public readonly LintSeverity $severity,
        public readonly string $category,
        public readonly string $message,
        public readonly ?string $recipe = null,
        ?string $file = null,
    ) {
        // Normalize to forward slashes so reports are identical on Windows and Unix.
        $this->file = null !== $file ? strtr($file, '\\', '/') : null;
    }
}
