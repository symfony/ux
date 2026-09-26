<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Twig;

use Symfony\UX\Breadcrumb\BreadcrumbItem;
use Symfony\UX\Breadcrumb\Exception\InvalidArgumentException;
use Twig\Environment;

/**
 * Renders resolved breadcrumb items to HTML.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class BreadcrumbRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $defaultTheme = '@UXBreadcrumb/theme/default.html.twig',
    ) {
    }

    /**
     * @param list<BreadcrumbItem>    $items
     * @param array<array-key, mixed> $attributes
     */
    public function renderBreadcrumb(array $items, array $attributes = [], ?string $theme = null): string
    {
        $theme ??= $this->defaultTheme;
        if ('' === trim($theme)) {
            throw new InvalidArgumentException('The "theme" argument must be a non-empty string or null.');
        }

        return $this->twig->render($theme, [
            'items' => $items,
            'attributes' => $this->normalizeAttributes($attributes),
        ]);
    }

    /**
     * @param array<array-key, mixed> $attributes
     *
     * @return array<string, bool|float|int|string|null>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $name => $value) {
            if (!\is_string($name) || 1 !== preg_match('/^[^\s"\'<>\/=]+$/uD', $name)) {
                throw new InvalidArgumentException('Invalid attribute name in "attributes".');
            }
            if (null !== $value && !\is_scalar($value) && !$value instanceof \Stringable) {
                throw new InvalidArgumentException(\sprintf('Attribute "%s" in "attributes" is not scalar or Stringable.', $name));
            }

            $normalized[$name] = $value instanceof \Stringable ? (string) $value : $value;
        }

        return $normalized;
    }
}
