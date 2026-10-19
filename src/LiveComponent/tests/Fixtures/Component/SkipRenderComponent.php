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

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;

#[AsLiveComponent('skip_render_component', template: 'components/skip_render_component.html.twig')]
class SkipRenderComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    public static int $preReRenderCalls = 0;

    #[LiveProp(writable: true, url: true)]
    public int $count = 0;

    #[LiveAction]
    public function increment(): LiveResponse
    {
        ++$this->count;

        return LiveResponse::skipRender();
    }

    #[LiveAction]
    public function incrementWithEvents(): LiveResponse
    {
        ++$this->count;
        $this->emit('countIncremented', ['count' => $this->count]);
        $this->dispatchBrowserEvent('count:incremented', ['count' => $this->count]);

        return LiveResponse::skipRender();
    }

    #[LiveAction]
    public function incrementAndRender(): void
    {
        ++$this->count;
    }

    #[LiveAction]
    public function incrementAndDownload(): LiveResponse
    {
        ++$this->count;

        return LiveResponse::downloadUrl('/downloads/report.csv');
    }

    #[PreReRender]
    public function beforeReRender(): void
    {
        ++self::$preReRenderCalls;
    }
}
