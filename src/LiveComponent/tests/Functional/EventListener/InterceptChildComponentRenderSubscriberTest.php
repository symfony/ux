<?php

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
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;

final class InterceptChildComponentRenderSubscriberTest extends KernelTestCase
{
    use HasBrowser;
    use LiveComponentTestHelper;

    // The actual ids and fingerprints that will happen inside todo_list.html.twig
    // if you pass in 3 "items" with data that matches what's used by default
    // in buildUrlForTodoListComponent
    private static array $actualTodoItemFingerprints = [
        AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX . '0' => 'LwqODySoRx3q+v64EzalGouzpSHWKIm0jENTUGtQloE=',
        AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX . '1' => 'gn9PcPUqL0tkeLSw0ZuhOj96dwIpiBmJPoO5NPync2o=',
        AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX . '2' => 'ndV00y/qOSH11bjOKGDJVRsxANtbudYB6K8D46viUI8=',
    ];

    public function testItAllowsFullChildRenderOnMissingFingerprints(): void
    {
        $this->browser()
            ->visit($this->buildUrlForTodoListComponent([]))
            ->assertSuccessful()
            ->assertHtml()
            ->assertElementCount('ul li', 3)
            ->assertContains('todo item: wake up')
            ->assertContains('todo item: high five a friend')
        ;
    }

    public function testItRendersEmptyElementOnMatchingFingerprint(): void
    {
        $this->browser()
            ->visit($this->buildUrlForTodoListComponent(self::$actualTodoItemFingerprints))
            ->assertSuccessful()
            ->assertHtml()
            // no lis (because we render a div always)
            ->assertElementCount('ul li', 0)
            // because we actually slip in a div element
            ->assertElementCount('ul div', 3)
            ->assertNotContains('todo item')
        ;
    }

    public function testItRendersEmptyElementOnMatchingFingerprintWithCustomDataLiveId(): void
    {
        $fingerPrintsWithCustomLiveId = [];
        foreach (array_values(self::$actualTodoItemFingerprints) as $key => $fingerprintValue) {
            // creating fingerprints to match todo_list.html.twig
            $fingerPrintsWithCustomLiveId['todo-item-'.$key + 1] = $fingerprintValue;
        }

        $this->browser()
            ->visit($this->buildUrlForTodoListComponent($fingerPrintsWithCustomLiveId, true))
            ->assertSuccessful()
            ->assertHtml()
            // no lis (because we render a div always)
            ->assertElementCount('ul li', 0)
            // because we actually slip in a div element
            ->assertElementCount('ul div', 3)
            ->assertNotContains('todo item')
        ;
    }

    public function testItRendersNewPropWhenFingerprintDoesNotMatch(): void
    {
        $fingerprints = self::$actualTodoItemFingerprints;
        $fingerprints[AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX . '1'] = 'wrong fingerprint';

        $this->browser()
            ->visit($this->buildUrlForTodoListComponent($fingerprints))
            ->assertSuccessful()
            ->assertHtml()
            // no lis (because we render a div always)
            ->assertElementCount('ul li', 0)
            ->assertElementCount('ul div', 3)
            ->assertNotContains('todo item')
            ->use(function (AbstractBrowser $browser) {
                $content = $browser->getResponse()->getContent();

                // 1st and 3rd render empty
                // fingerprint changed in 2nd, so it renders new fingerprint + props
                $this->assertStringContainsString(sprintf(
                    '<div data-live-id="%s0"></div>',
                    AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX
                ), $content);
                $this->assertStringContainsString(sprintf(
                    '<div data-live-id="%s1" data-live-fingerprint-value="gn9PcPUqL0tkeLSw0ZuhOj96dwIpiBmJPoO5NPync2o&#x3D;" data-live-props-value="&#x7B;&quot;_checksum&quot;&#x3A;&quot;YrPg4mHsB82fR&#x5C;&#x2F;VmTL3gJIX32kS8HvWy&#x5C;&#x2F;9uKSs&#x5C;&#x2F;aPfk&#x3D;&quot;&#x7D;"></div>',
                    AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX
                ), $content);
                $this->assertStringContainsString(sprintf(
                    '<div data-live-id="%s2"></div>',
                    AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX
                ), $content);
            });
    }

    private function buildUrlForTodoListComponent(array $childrenFingerprints, bool $includeLiveId = false): string
    {
        $component = $this->mountComponent('todo_list', [
            'items' => [
                ['text' => 'wake up'],
                ['text' => 'high five a friend'],
                ['text' => 'take a nap'],
            ],
            'includeDataLiveId' => $includeLiveId,
        ]);

        $dehydrated = $this->dehydrateComponent($component);

        $queryData = [
            'data' => json_encode($dehydrated->all()),
        ];

        if ($childrenFingerprints) {
            $queryData['childrenFingerprints'] = json_encode($childrenFingerprints);
        }

        return '/_components/todo_list?'.http_build_query($queryData);
    }
}
