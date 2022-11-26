<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

use Twig\Environment;

/**
 * @experimental
 *
 * @internal
 */
final class TwigAttributeHelper
{
    public function __construct(private Environment $twig)
    {
    }

    public function escapeAttribute(string $value): string
    {
        return twig_escape_filter($this->twig, $value, 'html_attr');
    }
}
