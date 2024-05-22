<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Extension;

use Tempest\Highlight\Highlighter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HighlightExtension extends AbstractExtension
{
    public function __construct(
        private readonly Highlighter $highlighter,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight', $this->highlight(...), ['is_safe' => ['html']]),
        ];
    }

    public function highlight(string $code, string $language, ?int $startAt = null): string
    {
        if (null !== $startAt) {
            return $this->highlighter->withGutter($startAt)->parse($code, $language);
        }

        return $this->highlighter->parse($code, $language);
    }
}
