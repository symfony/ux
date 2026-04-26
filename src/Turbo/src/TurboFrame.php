<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides helper methods to detect Turbo Frame requests.
 *
 * When a navigation is scoped to a Turbo Frame, Turbo sets the "Turbo-Frame"
 * HTTP header to the frame's id attribute value.
 *
 * Inspired by Turbo Rails
 * ({@see https://github.com/hotwired/turbo-rails/blob/main/app/controllers/turbo/frames/frame_request.rb}).
 *
 * @author Sébastien JEAN <sebastien.jean76@gmail.com>
 *
 * @see https://turbo.hotwired.dev/handbook/frames
 */
final class TurboFrame
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * Returns true if the current request was triggered by a Turbo Frame.
     *
     * Turbo sets the "Turbo-Frame" HTTP header when navigating inside a frame.
     *
     * @phpstan-assert-if-true string $this->getRequestId()
     */
    public function isFrameRequest(): bool
    {
        return null !== $this->getRequestId();
    }

    /**
     * Returns the ID of the Turbo Frame that triggered the current request,
     * or null if the request was not triggered by a Turbo Frame.
     */
    public function getRequestId(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->headers->get('Turbo-Frame');
    }
}
