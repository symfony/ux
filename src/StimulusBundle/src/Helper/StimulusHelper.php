<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Helper;

use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class StimulusHelper
{
    private Environment $twig;

    public function __construct(?Environment $twig)
    {
        // Twig needed just for its escaping mechanism
        $this->twig = $twig ?? new Environment(new ArrayLoader());
    }

    public function createStimulusAttributes(): StimulusAttributes
    {
        return new StimulusAttributes($this->twig);
    }
}
