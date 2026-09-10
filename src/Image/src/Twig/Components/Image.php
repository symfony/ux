<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig\Components;

use Symfony\UX\Image\ImageAsset;

/**
 * Twig component for rendering images with responsive features.
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class Image
{
    public ?ImageAsset $src = null;
    public ?string $alt = null;
    public ?string $class = null;
    public ?string $variant = null;
    public bool $lazy = true;
    /** @var list<string>|null */
    public ?array $srcset = null;
    public ?string $sizes = null;
    public ?string $fetchpriority = null;
    public ?string $decoding = 'async';
}
