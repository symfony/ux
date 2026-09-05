<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Renderer;

use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\Layout;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class RenderOptions
{
    public readonly ?Fit $fit;

    /**
     * @param list<int>|null                       $breakpoints
     * @param array<string, array<string, scalar>> $operations
     */
    public function __construct(
        public readonly Layout $layout = Layout::Constrained,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        ?Fit $fit = null,
        public readonly ?string $format = null,
        public readonly ?int $quality = null,
        public readonly bool $priority = false,
        public readonly string $objectFit = 'cover',
        public readonly ?array $breakpoints = null,
        public readonly array $operations = [],
    ) {
        if (null === $width && \in_array($layout, [Layout::Fixed, Layout::Constrained], true)) {
            throw new InvalidArgumentException(\sprintf('The "%s" layout requires a width.', $layout->value));
        }
        if (null === $height && Layout::FullWidth === $layout) {
            throw new InvalidArgumentException(\sprintf('The "%s" layout requires a height.', $layout->value));
        }

        // Both dimensions given signals an intended ratio (already asserted in the generated CSS), so default to cropping to it.
        $this->fit = $fit ?? (null !== $width && null !== $height ? Fit::Cover : null);
    }
}
