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
        static $known;
        $known ??= \array_slice(array_column(new \ReflectionMethod(ImageUrlGenerator::class, 'generate')->getParameters(), 'name'), 1);

        if ([] !== $unknown = array_diff(array_keys($options), $known)) {
            throw new InvalidArgumentException(\sprintf('Unknown image URL option "%s": expected one of "%s".', implode('", "', $unknown), implode('", "', $known)));
        }

        if (isset($options['fit'])) {
            $options['fit'] = RenderOptionsFactory::fit($options['fit']);
        }

        return $this->urlGenerator->generate($src, ...array_filter($options, static fn (mixed $value): bool => null !== $value));
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $attributes
     */
    public function renderImage(string $src, string $alt, array $options = [], array $attributes = []): string
    {
        $rendered = $this->renderer->render($src, $alt, RenderOptionsFactory::createFromArray($options));

        return ImageMarkup::img($rendered, new ComponentAttributes(ImageMarkup::normalizeAttributes($attributes), $this->twig->getRuntime(EscaperRuntime::class)));
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $attributes
     */
    public function renderPicture(string $src, string $alt, array $options = [], array $attributes = []): string
    {
        $rendered = $this->renderer->renderPicture($src, $alt, RenderOptionsFactory::createFromArray($options));

        return ImageMarkup::picture($rendered, new ComponentAttributes(ImageMarkup::normalizeAttributes($attributes), $this->twig->getRuntime(EscaperRuntime::class)));
    }
}
