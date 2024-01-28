<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;

/**
 * Resets the "deterministic id calculator" after each full "parent" component has finished rendering.
 *
 * When a component (and all of its children) have finished rendering, this resets
 * the internal "counter" on the id calculator. Suppose this situation:
 *
 *      Parent Component A
 *          Child Component 1
 *
 *      Parent Component B
 *          Child Component 1
 *
 * If we didn't reset, the deterministic id of the two "Child Component 1" objects
 * would be *different*: the first would end with "-0" and the second with "-1".
 * This is a problem because when "Parent Component B" renders later via Ajax,
 * its child will generate an id ending in "-0", since there wasn't a previous
 * Child Component 1 that rendered during that request.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
final class ResetDeterministicIdSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DeterministicTwigIdCalculator $idCalculator,
        private ComponentStack $componentStack,
    ) {
    }

    public function onPostRender(): void
    {
        if (!$this->componentStack->getCurrentComponent()) {
            $this->idCalculator->reset();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [PostRenderEvent::class => 'onPostRender'];
    }
}
