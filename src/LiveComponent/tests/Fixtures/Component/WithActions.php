<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Component;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('with_actions')]
final class WithActions
{
    use DefaultActionTrait;

    #[LiveProp]
    public array $items = ['initial'];

    #[LiveAction]
    public function add(#[LiveArg] string $what, UrlGeneratorInterface $router): void
    {
        $this->items[] = $what;
    }

    #[LiveAction]
    public function redirect(UrlGeneratorInterface $router): RedirectResponse
    {
        return new RedirectResponse($router->generate('homepage'));
    }

    #[LiveAction]
    public function exception(): void
    {
        throw new \RuntimeException('Exception message');
    }

    public function nonLive(): void
    {
    }
}
