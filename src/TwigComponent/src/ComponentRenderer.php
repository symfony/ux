<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class ComponentRenderer
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render(ComponentInterface $component): string
    {
        // TODO: Template attribute/annotation/interface to customize
        // TODO: Self-Rendering components?
        $templateName = sprintf('components/%s.html.twig', $component::getComponentName());

        return $this->twig->render($templateName, ['this' => $component]);
    }
}
