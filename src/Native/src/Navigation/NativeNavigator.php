<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Navigation;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Native\EventListener\NativeListener;

/**
 * Redirects Hotwire Native apps to a historical location, and browsers to a regular page.
 *
 * Every redirection uses the 303 status code, so the next request is a GET, as Turbo expects after a form submission.
 */
final class NativeNavigator
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function isNative(): bool
    {
        return true === $this->requestStack->getCurrentRequest()?->attributes->get(NativeListener::NATIVE_ATTRIBUTE);
    }

    /**
     * Redirects to the historical location, which only a Hotwire Native app knows how to handle.
     *
     * @throws \LogicException When the current request does not come from a Hotwire Native app
     */
    public function redirect(HistoricalLocation $location): RedirectResponse
    {
        if (!$this->isNative()) {
            throw new \LogicException(\sprintf('Cannot redirect to the "%s" historical location: the current request does not come from a Hotwire Native app. Use "%s::redirectOr()" to redirect browsers to a regular page.', $location->value, self::class));
        }

        return $this->redirectToHistoricalLocation($location);
    }

    /**
     * Redirects a Hotwire Native app to the historical location, and a browser to the given URL.
     */
    public function redirectOr(HistoricalLocation $location, string $url): RedirectResponse
    {
        if ($this->isNative()) {
            return $this->redirectToHistoricalLocation($location);
        }

        return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }

    /**
     * Redirects a Hotwire Native app to the historical location, and a browser to the given route.
     *
     * @param array<string, mixed> $parameters
     */
    public function redirectOrToRoute(HistoricalLocation $location, string $route, array $parameters = []): RedirectResponse
    {
        if ($this->isNative()) {
            return $this->redirectToHistoricalLocation($location);
        }

        $url = $this->urlGenerator->generate($route, $parameters);

        return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }

    /**
     * Redirects a Hotwire Native app to the historical location, and a browser back to the previous page.
     *
     * The browser goes back only when the Referer header comes from the same origin, otherwise it goes to the fallback URL.
     */
    public function redirectOrBackOr(HistoricalLocation $location, string $fallbackUrl): RedirectResponse
    {
        if ($this->isNative()) {
            return $this->redirectToHistoricalLocation($location);
        }

        $request = $this->requestStack->getCurrentRequest();
        $referer = $request?->headers->get('Referer');
        $isSameOrigin = null !== $referer && str_starts_with($referer.'/', $request->getSchemeAndHttpHost().'/');

        return new RedirectResponse($isSameOrigin ? $referer : $fallbackUrl, Response::HTTP_SEE_OTHER);
    }

    private function redirectToHistoricalLocation(HistoricalLocation $location): RedirectResponse
    {
        $url = $this->urlGenerator->generate(\sprintf('ux_native_%s_historical_location', $location->value));

        return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }
}
