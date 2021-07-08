<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixture\Component;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\BeforeReRender;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsLiveComponent('component2', defaultAction: 'defaultAction()')]
final class Component2
{
    #[LiveProp]
    public int $count = 1;

    public bool $preDehydrateCalled = false;

    public bool $postHydrateCalled = false;

    public bool $beforeReRenderCalled = false;

    public function defaultAction(): void
    {
    }

    #[LiveAction]
    public function increase(): void
    {
        ++$this->count;
    }

    #[LiveAction]
    public function redirect(UrlGeneratorInterface $urlGenerator): RedirectResponse
    {
        return new RedirectResponse($urlGenerator->generate('homepage'));
    }

    #[PreDehydrate]
    public function preDehydrateMethod(): void
    {
        $this->preDehydrateCalled = true;
    }

    #[PostHydrate]
    public function postHydrateMethod(): void
    {
        $this->postHydrateCalled = true;
    }

    #[BeforeReRender]
    public function beforeReRenderMethod(): void
    {
        $this->beforeReRenderCalled = true;
    }
}
