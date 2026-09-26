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

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\UX\Breadcrumb\BreadcrumbItem;
use Symfony\UX\Breadcrumb\BreadcrumbResolver;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\BreadcrumbTrailProvider;
use Twig\Attribute\AsTwigFunction;

/**
 * Resolution happens here rather than in the listener, so a request that renders no breadcrumb, such as a redirect, a Turbo Stream or JSON, costs nothing.
 *
 * Twig fetches this class through its runtime loader, on the first call and not before.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class BreadcrumbExtension
{
    /**
     * A page renders the trail twice, for the visible bar and for the JSON-LD node, so the resolved items are memoized per reference type and per locale: a template may render the same trail twice inside LocaleSwitcher::runWithLocale().
     *
     * Keyed by the trail, which lives and dies with its request: entries cannot leak between the requests a FrankenPHP worker serves.
     *
     * @var \WeakMap<BreadcrumbTrail, array<string, list<BreadcrumbItem>>>
     */
    private \WeakMap $resolved;

    public function __construct(
        private readonly BreadcrumbTrailProvider $trailProvider,
        private readonly BreadcrumbResolver $resolver,
        private readonly BreadcrumbRenderer $renderer,
        private readonly ?LocaleAwareInterface $localeAware = null,
    ) {
        $this->resolved = new \WeakMap();
    }

    /**
     * @return list<BreadcrumbItem>
     */
    #[AsTwigFunction('ux_breadcrumb_items')]
    public function getBreadcrumb(bool $absolute = false): array
    {
        $trail = $this->trailProvider->getTrail();

        if (!$trail instanceof BreadcrumbTrail || $trail->isEmpty()) {
            return [];
        }

        $referenceType = $absolute
            ? UrlGeneratorInterface::ABSOLUTE_URL
            : UrlGeneratorInterface::ABSOLUTE_PATH;

        $key = $referenceType.'@'.($this->localeAware?->getLocale() ?? '');

        $memo = $this->resolved[$trail] ?? [];

        if (!isset($memo[$key])) {
            $memo[$key] = $this->resolver->resolve($trail, $referenceType);
            $this->resolved[$trail] = $memo;
        }

        return $memo[$key];
    }

    /**
     * @param array<array-key, mixed> $attributes
     */
    #[AsTwigFunction('ux_breadcrumb', isSafe: ['html'])]
    public function renderBreadcrumb(array $attributes = [], ?string $theme = null): string
    {
        return $this->renderer->renderBreadcrumb($this->getBreadcrumb(), $attributes, $theme);
    }
}
