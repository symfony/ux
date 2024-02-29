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
 * Helper for building an array of attributes for the live controller element.
 *
 * @internal
 */
final class TwigAttributeHelperFactory
{
    public function __construct(private Environment $twig)
    {
    }

    public function create(): LiveAttributesCollection
    {
        return new LiveAttributesCollection($this->twig);
    }
}
