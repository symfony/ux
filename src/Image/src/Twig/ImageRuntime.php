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

use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\ImageUrlGenerator;
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
    private const URL_OPTIONS = ['width', 'height', 'fit', 'format', 'quality', 'operations'];

    public function __construct(
        private readonly ImageRendererInterface $renderer,
        private readonly ImageUrlGenerator $urlGenerator,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderUrl(string $src, array $options = []): string
    {
        if ([] !== $unknown = array_diff(array_keys($options), self::URL_OPTIONS)) {
            throw new InvalidArgumentException(\sprintf('Unknown image URL option "%s": expected one of "%s".', implode('", "', $unknown), implode('", "', self::URL_OPTIONS)));
        }

        $fit = $options['fit'] ?? null;

        return $this->urlGenerator->generate(
            $src,
            $options['width'] ?? null,
            $options['height'] ?? null,
            null !== $fit ? RenderOptionsFactory::fit($fit) : null,
            $options['format'] ?? null,
            $options['quality'] ?? null,
            $options['operations'] ?? [],
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function renderImage(string $src, string $alt, array $options = []): string
    {
        $rendered = $this->renderer->render($src, $alt, RenderOptionsFactory::createFromArray($options));

        return ImageMarkup::render($rendered, new ComponentAttributes([], $this->twig->getRuntime(EscaperRuntime::class)));
    }
}
