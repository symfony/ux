<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig;

use Symfony\UX\Image\Renderer\ImageRendererInterface;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ImageRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly ImageRendererInterface $renderer,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderImage(string $src, string $alt, array $options = []): string
    {
        $rendered = $this->renderer->render($src, $alt, RenderOptionsFactory::createFromArray($options));

        return $this->twig->render('@UXImage/components/Image.html.twig', [
            'rendered' => $rendered,
            'attributes' => new ComponentAttributes([], $this->twig->getRuntime(EscaperRuntime::class)),
        ]);
    }
}
