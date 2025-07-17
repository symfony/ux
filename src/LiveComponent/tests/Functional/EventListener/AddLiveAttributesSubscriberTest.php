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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AddLiveAttributesSubscriberTest extends KernelTestCase
{
    use HasBrowser;
    use LiveComponentTestHelper;

    /**
     * The deterministic id of the "todo_item" components in todo_list.html.twig.
     * If that template changes, this will need to be updated.
     */
    public const TODO_ITEM_DETERMINISTIC_PREFIX = 'live-1715058793-';
    public const TODO_ITEM_DETERMINISTIC_PREFIX_EMBEDDED = 'live-2285361477-';

    public function testInitLiveComponent(): void
    {
        $div = $this->browser()
            ->visit('/render-template/render_component_with_writable_props')
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $props = json_decode($div->attr('data-live-props-value'), true);

        $this->assertSame('live', $div->attr('data-controller'));
        $this->assertSame('/_components/component_with_writable_props', $div->attr('data-live-url-value'));
        $this->assertCount(4, $props);
        $this->assertSame(5, $props['max']);
        $this->assertSame(1, $props['count']);
        $this->assertArrayHasKey('@checksum', $props);
        $this->assertArrayHasKey('@attributes', $props);
        $this->assertArrayHasKey('id', $props['@attributes']);
    }

    public function testCanUseCustomAttributesVariableName(): void
    {
        $div = $this->browser()
            ->visit('/render-template/render_custom_attributes')
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $props = json_decode($div->attr('data-live-props-value'), true);

        $this->assertSame('live', $div->attr('data-controller'));
        $this->assertSame('/_components/custom_attributes', $div->attr('data-live-url-value'));
        $this->assertArrayHasKey('@checksum', $props);
    }

    public function testItAddsIdAndFingerprintToChildComponent(): void
    {
        $templateName = 'components/todo_list.html.twig';
        $obscuredName = 'd9bcb8935cbb4282ac5d227fc82ae782';
        $this->addTemplateMap($obscuredName, $templateName);

        $ul = $this->browser()
            ->visit('/render-template/render_todo_list')
            ->assertSuccessful()
            ->crawler()
            ->filter('ul')
        ;

        $lis = $ul->children('li');
        // deterministic id: should not change, and counter should increase
        $this->assertSame(self::TODO_ITEM_DETERMINISTIC_PREFIX.'0', $lis->first()->attr('id'));
        $this->assertSame(self::TODO_ITEM_DETERMINISTIC_PREFIX.'1', $lis->last()->attr('id'));

        // the id attribute also needs to be part of the "props" so that it persists on renders
        $props = json_decode($lis->first()->attr('data-live-props-value'), true);
        $attributesProps = $props['@attributes'];
        $this->assertArrayHasKey('id', $attributesProps);

        // fingerprints
        // first and last both have the same length "text" input, and since "textLength"
        // is the only updateFromParent prop, they should have the same fingerprint
        $this->assertSame('7uQSVj4d4n3/RCVRkyLDCW9vLBxIAQVCEb1Rr3CJpfc=', $lis->first()->attr('data-live-fingerprint-value'));
        $this->assertSame('7uQSVj4d4n3/RCVRkyLDCW9vLBxIAQVCEb1Rr3CJpfc=', $lis->last()->attr('data-live-fingerprint-value'));
        // middle has a different fingerprint
        $this->assertSame('XSdvsiDR8VG0GFDQbOnj74XfSmfL6WrzMbSQcdIRhSs=', $lis->eq(1)->attr('data-live-fingerprint-value'));
    }

    public function testItDoesNotOverrideDataLiveIdIfSpecified(): void
    {
        $templateName = 'components/todo_list.html.twig';
        $obscuredName = 'a643d58357b14c9bb077f0c00a742059';
        $this->addTemplateMap($obscuredName, $templateName);

        $ul = $this->browser()
            ->visit('/render-template/render_todo_list_with_live_id')
            ->assertSuccessful()
            ->crawler()
            ->filter('ul')
        ;

        $lis = $ul->children('li');
        // deterministic id: is not used: id was passed in manually
        $this->assertSame('todo-item-1', $lis->first()->attr('id'));
        $this->assertSame('todo-item-3', $lis->last()->attr('id'));
    }

    public function testQueryStringMappingAttribute()
    {
        $div = $this->browser()
            ->visit('/render-template/render_component_with_url_bound_props')
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $queryMapping = json_decode($div->attr('data-live-query-mapping-value'), true);
        $expected = [
            'stringProp' => ['name' => 'stringProp'],
            'intProp' => ['name' => 'intProp'],
            'arrayProp' => ['name' => 'arrayProp'],
            'objectProp' => ['name' => 'objectProp'],
            'field1' => ['name' => 'field1'],
            'field2' => ['name' => 'field2'],
            'maybeBoundProp' => ['name' => 'maybeBoundProp'],
            'boundPropWithAlias' => ['name' => 'q'],
            'boundPropWithCustomAlias' => ['name' => 'customAlias'],
        ];

        $this->assertEquals($expected, $queryMapping);
    }

    public function testAbsoluteUrl(): void
    {
        $div = $this->browser()
            ->visit('/render-template/render_with_absolute_url')
            ->assertSuccessful()
            ->assertContains('Count: 0')
            ->crawler()
            ->filter('div')
        ;

        $props = json_decode($div->attr('data-live-props-value'), true);

        $this->assertSame('live', $div->attr('data-controller'));
        $this->assertSame('http://localhost/_components/with_absolute_url', $div->attr('data-live-url-value'));
        $this->assertCount(3, $props);
        $this->assertArrayHasKey('@checksum', $props);
        $this->assertArrayHasKey('@attributes', $props);
        $this->assertArrayHasKey('id', $props['@attributes']);
        $this->assertArrayHasKey('count', $props);
        $this->assertSame($props['count'], 0);
    }

    public function testAbsoluteUrlWithLiveQueryProp()
    {
        $props = [];
        $div = $this->browser()
            ->get('/render-template/render_with_absolute_url?count=1')
            ->assertSuccessful()
            ->assertContains('Count: 1')
            ->use(function (Crawler $crawler) use (&$props) {
                $div = $crawler->filter('div')->first();
                $props = json_decode($div->attr('data-live-props-value'), true);
            })
            ->post('http://localhost/_components/with_absolute_url/increase', [
                'body' => ['data' => json_encode(['props' => $props])],
            ])
            ->assertContains('Count: 2')
            ->crawler()
            ->filter('div')
        ;

        $props = json_decode($div->attr('data-live-props-value'), true);

        $this->assertSame('live', $div->attr('data-controller'));
        $this->assertSame('http://localhost/_components/with_absolute_url', $div->attr('data-live-url-value'));
        $this->assertCount(3, $props);
        $this->assertArrayHasKey('@checksum', $props);
        $this->assertArrayHasKey('@attributes', $props);
        $this->assertArrayHasKey('id', $props['@attributes']);
        $this->assertArrayHasKey('count', $props);
        $this->assertSame($props['count'], 2);
    }
}
