<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CssExtension extends AbstractExtension
{
    public function __construct(
        private readonly CssNodeVisitor $nodeVisitor,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('css', [CssRuntime::class, 'css']),
        ];
    }

    public function getNodeVisitors(): array
    {
        return [$this->nodeVisitor];
    }
}
