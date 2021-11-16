<?php

namespace Symfony\UX\TwigComponent\Tests\Fixture\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\SelfRenderingInterface;
use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsTwigComponent('component_e')]
final class ComponentE implements SelfRenderingInterface
{
    public function render(Environment $twig): string
    {
        return $twig->render('self_rendering.html.twig', ['foo' => 'bar']);
    }
}
