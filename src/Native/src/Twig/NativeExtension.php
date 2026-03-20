<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Native\EventListener\NativeListener;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NativeExtension extends AbstractExtension
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ux_is_native', [$this, 'isNative']),
        ];
    }

    public function isNative(): bool
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            return false;
        }

        return $request->attributes->get(NativeListener::NATIVE_ATTRIBUTE, false);
    }
}
