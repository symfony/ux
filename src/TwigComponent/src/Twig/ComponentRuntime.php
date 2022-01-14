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

use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Twig\Environment;
use Twig\Extension\EscaperExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class ComponentRuntime
{
    private ComponentFactory $componentFactory;
    private ComponentRenderer $componentRenderer;
    private bool $safeClassesRegistered = false;

    public function __construct(ComponentFactory $componentFactory, ComponentRenderer $componentRenderer)
    {
        $this->componentFactory = $componentFactory;
        $this->componentRenderer = $componentRenderer;
    }

    public function render(Environment $twig, string $name, array $props = []): string
    {
        if (!$this->safeClassesRegistered) {
            $twig->getExtension(EscaperExtension::class)->addSafeClass(ComponentAttributes::class, ['html']);

            $this->safeClassesRegistered = true;
        }

        return $this->componentRenderer->render(
            $this->componentFactory->create($name, $props),
            $this->componentFactory->configFor($name)['template']
        );
    }
}
