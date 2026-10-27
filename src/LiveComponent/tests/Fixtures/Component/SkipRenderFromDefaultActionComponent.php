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
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\LiveResponse;

#[AsLiveComponent('skip_render_from_default_action', template: 'components/skip_render_component.html.twig')]
class SkipRenderFromDefaultActionComponent
{
    #[LiveProp]
    public int $count = 0;

    public function __invoke(): LiveResponse
    {
        return LiveResponse::skipRender();
    }
}
