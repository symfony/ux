<?php

namespace Symfony\UX\TwigComponent;

use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface SelfRenderingInterface
{
    public function render(Environment $twig): string;
}
