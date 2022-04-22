<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class ComponentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [ComponentRuntime::class, 'render'], ['is_safe' => ['all']]),
        ];
    }
}
