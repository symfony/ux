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
use Symfony\UX\LiveComponent\Attribute\BeforeReRender;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;
use Symfony\UX\LiveComponent\LiveComponentInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Component2 implements LiveComponentInterface
{
    /**
     * @LiveProp
     */
    public int $count = 1;

    public bool $preDehydrateCalled = false;

    public bool $postHydrateCalled = false;

    public bool $beforeReRenderCalled = false;

    /**
     * @LiveAction
     */
    public function increase(): void
    {
        ++$this->count;
    }

    /**
     * @LiveAction
     */
    public function redirect(): RedirectResponse
    {
        return new RedirectResponse('/');
    }

    public static function getComponentName(): string
    {
        return 'component2';
    }

    /**
     * @PreDehydrate()
     */
    public function preDehydrateMethod(): void
    {
        $this->preDehydrateCalled = true;
    }

    /**
     * @PostHydrate()
     */
    public function postHydrateMethod(): void
    {
        $this->postHydrateCalled = true;
    }

    /**
     * @BeforeReRender()
     */
    public function beforeReRenderMethod(): void
    {
        $this->beforeReRenderCalled = true;
    }
}
