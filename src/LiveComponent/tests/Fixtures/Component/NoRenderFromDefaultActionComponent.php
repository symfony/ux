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

#[AsLiveComponent('no_render_from_default_action', template: 'components/no_render_component.html.twig')]
class NoRenderFromDefaultActionComponent
{
    #[LiveProp]
    public int $count = 0;

    public function __invoke(): LiveResponse
    {
        return LiveResponse::noRender();
    }
}
