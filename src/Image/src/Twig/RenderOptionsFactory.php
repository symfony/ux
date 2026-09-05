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
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\Layout;
use Symfony\UX\Image\Renderer\RenderOptions;

/**
 * Converts the loose, string-typed values coming from Twig (the component's
 * props or the ux_image() options array) into a {@see RenderOptions}.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RenderOptionsFactory
{
    /**
     * Spreading an unvetted array over {@see create()} would raise a raw PHP "Unknown named
     * parameter" Error, so ux_image()'s options array goes through here first.
     *
     * @param array<string, mixed> $options
     */
    public static function createFromArray(array $options): RenderOptions
    {
        static $known;
        $known ??= array_column(new \ReflectionMethod(self::class, 'create')->getParameters(), 'name');

        if ([] !== $unknown = array_diff(array_keys($options), $known)) {
            throw new InvalidArgumentException(\sprintf('Unknown image option "%s": expected one of "%s".', implode('", "', $unknown), implode('", "', $known)));
        }

        return self::create(...$options);
    }

    /**
     * @param list<int>|null                       $breakpoints
     * @param array<string, array<string, scalar>> $operations
     */
    public static function create(
        string $layout = 'constrained',
        ?int $width = null,
        ?int $height = null,
        ?string $fit = null,
        ?string $format = null,
        ?int $quality = null,
        bool $priority = false,
        string $objectFit = 'cover',
        ?array $breakpoints = null,
        array $operations = [],
    ): RenderOptions {
        return new RenderOptions(
            layout: self::layout($layout),
            width: $width,
            height: $height,
            fit: null !== $fit ? self::fit($fit) : null,
            format: $format,
            quality: $quality,
            priority: $priority,
            objectFit: $objectFit,
            breakpoints: $breakpoints,
            operations: $operations,
        );
    }

    private static function layout(string $layout): Layout
    {
        return Layout::tryFrom($layout) ?? throw new InvalidArgumentException(\sprintf('Invalid "layout" value "%s": expected one of "%s".', $layout, implode('", "', array_column(Layout::cases(), 'value'))));
    }

    private static function fit(string $fit): Fit
    {
        return Fit::tryFrom($fit) ?? throw new InvalidArgumentException(\sprintf('Invalid "fit" value "%s": expected one of "%s".', $fit, implode('", "', array_column(Fit::cases(), 'value'))));
    }
}
