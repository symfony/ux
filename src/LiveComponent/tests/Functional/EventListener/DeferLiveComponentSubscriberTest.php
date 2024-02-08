<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;

final class DeferLiveComponentSubscriberTest extends KernelTestCase
{
    use HasBrowser;
    use LiveComponentTestHelper;

    public function testItSetsDeferredTemplateIfLiveIdNotPassed(): void
    {
        $div = $this->browser()
            ->visit('/render-template/render_deferred_component')
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $this->assertSame('', trim($div->html()));
        $this->assertSame('live:connect->live#$render', $div->attr('data-action'));

        $component = $this->mountComponent('deferred_component', [
            'id' => $div->attr('id'),
        ]);

        $dehydrated = $this->dehydrateComponent($component);

        $div = $this->browser()
            ->visit('/_components/deferred_component?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $this->assertSame('Long awaited data', $div->html());
    }

    public function testItIncludesGivenTemplateWhileLoadingDeferredComponent(): void
    {
        $div = $this->browser()
            ->visit('/render-template/render_deferred_component_with_template')
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $this->assertSame('I\'m loading a reaaaally slow live component', trim($div->html()));

        $component = $this->mountComponent('deferred_component', [
            'id' => $div->attr('id'),
        ]);

        $dehydrated = $this->dehydrateComponent($component);

        $div = $this->browser()
            ->visit('/_components/deferred_component?props='.urlencode(json_encode($dehydrated->getProps())))
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $this->assertStringContainsString('Long awaited data', $div->html());
    }

    public function testItAllowsToSetCustomLoadingHtmlTag(): void
    {
        $crawler = $this->browser()
            ->visit('/render-template/render_deferred_component_with_li_tag')
            ->assertSuccessful()
            ->crawler()
        ;

        $this->assertSame(0, $crawler->filter('div')->count());
        $this->assertSame(1, $crawler->filter('li')->count());
    }
}
