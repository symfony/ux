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
final class UXSourceComponent
{
    public string|array|null $srcset = null;
    public string|array|null $sizes = null;
    public ?string $media = null;
    public ?string $type = null;
    public ?int $width = null;
    public ?int $height = null;
}
