<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Twig\LiveComponentRuntime;

final class LiveComponentRuntimeTest extends KernelTestCase
{
    public function testGetLiveAction(): void
    {
        $runtime = self::getContainer()->get('ux.live_component.twig.component_runtime');
        \assert($runtime instanceof LiveComponentRuntime);

        $props = $runtime->liveAction('action-name');
        $this->assertSame('data-action="live#action" data-live-action-param="action-name"', $props);

        $props = $runtime->liveAction('action-name', ['prop1' => 'val1', 'someProp' => 'val2']);
        $this->assertSame('data-action="live#action" data-live-prop1-param="val1" data-live-some-prop-param="val2" data-live-action-param="action-name"', $props);

        $props = $runtime->liveAction('action-name', ['prop1' => 'val1', 'prop2' => 'val2'], ['debounce' => 300]);
        $this->assertSame('data-action="live#action" data-live-prop1-param="val1" data-live-prop2-param="val2" data-live-action-param="debounce(300)|action-name"', $props);

        $props = $runtime->liveAction('action-name:prevent', ['pro1' => 'val1', 'prop2' => 'val2'], ['debounce' => 300]);
        $this->assertSame('data-action="live#action:prevent" data-live-pro1-param="val1" data-live-prop2-param="val2" data-live-action-param="debounce(300)|action-name"', $props);

        $props = $runtime->liveAction('action-name:prevent', [], ['debounce' => 300]);
        $this->assertSame('data-action="live#action:prevent" data-live-action-param="debounce(300)|action-name"', $props);

        $props = $runtime->liveAction('action-name', [], [], 'keydown.esc');
        $this->assertSame('data-action="keydown.esc->live#action" data-live-action-param="action-name"', $props);
    }
}
