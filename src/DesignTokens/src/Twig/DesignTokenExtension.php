<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class DesignTokenExtension extends AbstractExtension
{
    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_token', [DesignTokenRuntime::class, 'getToken']),
            new TwigFunction('ux_token_css', [DesignTokenRuntime::class, 'renderCss'], ['is_safe' => ['html']]),
            new TwigFunction('ux_token_stylesheet', [DesignTokenRuntime::class, 'renderStylesheet'], ['is_safe' => ['html']]),
        ];
    }
}
