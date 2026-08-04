<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Twig;

use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\PaginationInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * @internal
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class PaginationExtension
{
    public function __construct(
        private readonly PaginationRenderer $renderer,
    ) {
    }

    /**
     * {{ ux_pagination(posts) }}
     * {{ ux_pagination(posts, {class: 'my-nav'},
     *     theme: '@UXPagination/theme/tailwind.html.twig', show_info: false) }}.
     *
     * @param PaginationInterface<mixed>       $pagination
     * @param array<array-key, mixed>          $attributes
     * @param array<array-key, mixed>          $navigationAttributes
     * @param array<array-key, mixed>|\Closure $linkAttributes
     */
    #[AsTwigFunction('ux_pagination', isSafe: ['html'])]
    public function renderPagination(
        PaginationInterface $pagination,
        array $attributes = [],
        ?string $theme = null,
        bool $showInfo = true,
        array $navigationAttributes = [],
        array|\Closure $linkAttributes = [],
    ): string {
        return $this->renderer->renderPagination(
            $pagination,
            $attributes,
            $theme,
            $showInfo,
            $navigationAttributes,
            $linkAttributes,
        );
    }

    /**
     * Called by the ux:pagination TwigComponent.
     *
     * @param array<string, mixed> $args
     */
    public function render(array $args = []): string
    {
        $pagination = $args['pagination'] ?? null;
        if (!$pagination instanceof PaginationInterface) {
            throw new InvalidArgumentException('The "pagination" argument must be a PaginationInterface instance.');
        }
        unset($args['pagination']);

        $attributes = $args['attributes'] ?? [];
        $theme = $args['theme'] ?? null;
        $showInfo = $args['showInfo'] ?? true;
        $navigationAttributes = $args['navigationAttributes'] ?? [];
        $linkAttributes = $args['linkAttributes'] ?? [];
        unset(
            $args['attributes'],
            $args['theme'],
            $args['showInfo'],
            $args['navigationAttributes'],
            $args['linkAttributes'],
        );

        if (!\is_array($attributes)) {
            throw new InvalidArgumentException('The "attributes" argument must be an array.');
        }
        if (null !== $theme && !\is_string($theme)) {
            throw new InvalidArgumentException('The "theme" argument must be a string or null.');
        }
        if (!\is_bool($showInfo)) {
            throw new InvalidArgumentException('The "showInfo" argument must be a boolean.');
        }
        if (!\is_array($navigationAttributes)) {
            throw new InvalidArgumentException('The "navigationAttributes" argument must be an array.');
        }
        if (!\is_array($linkAttributes) && !$linkAttributes instanceof \Closure) {
            throw new InvalidArgumentException('The "linkAttributes" argument must be an array or Closure.');
        }

        return $this->renderPagination(
            $pagination,
            array_replace($attributes, $args),
            $theme,
            $showInfo,
            $navigationAttributes,
            $linkAttributes,
        );
    }
}
