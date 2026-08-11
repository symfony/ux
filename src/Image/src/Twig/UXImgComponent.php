<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig;

/**
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 *
 * @internal
 */
final class UXImgComponent
{
    public ?string $src = null;
    public ?string $alt = null;
    public string|array|null $srcset = null;
    public string|array|null $sizes = null;
    public ?int $width = null;
    public ?int $height = null;
    public ?string $loading = null;
    public ?string $decoding = null;
    public ?string $fetchpriority = null;
    public ?string $crossorigin = null;
    public ?string $referrerpolicy = null;
    public ?bool $ismap = null;
    public ?string $usemap = null;
}
