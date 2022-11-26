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
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AddLiveAttributesSubscriberTest extends KernelTestCase
{
    use HasBrowser;

    public function testInitLiveComponent(): void
    {
        $div = $this->browser()
            ->visit('/render-template/render_component_with_writable_props')
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $props = json_decode($div->attr('data-live-props-value'), true);
        $data = json_decode($div->attr('data-live-data-value'), true);

        $this->assertSame('live', $div->attr('data-controller'));
        $this->assertSame('/_components/component_with_writable_props', $div->attr('data-live-url-value'));
        $this->assertNotNull($div->attr('data-live-csrf-value'));
        $this->assertCount(2, $props);
        $this->assertSame(5, $props['max']);
        $this->assertCount(1, $data);
        $this->assertSame(1, $data['count']);
        $this->assertArrayHasKey('_checksum', $props);
    }

    public function testCanUseCustomAttributes(): void
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
        $this->assertNotNull($div->attr('data-live-csrf-value'));
        $this->assertArrayHasKey('_checksum', $props);
    }

    public function testCanDisableCsrf(): void
    {
        $div = $this->browser()
            ->visit('/render-template/csrf')
            ->assertSuccessful()
            ->crawler()
            ->filter('div')
        ;

        $this->assertSame('live', $div->attr('data-controller'));
        $this->assertSame('/_components/disabled_csrf', $div->attr('data-live-url-value'));
        $this->assertNull($div->attr('data-live-csrf-value'));
    }

    public function testItAddsIdAndFingerprintToChildComponent(): void
    {
        $ul = $this->browser()
            ->visit('/render-template/render_todo_list')
            ->assertSuccessful()
            ->crawler()
            ->filter('ul')
        ;

        $lis = $ul->children('li');
        // deterministic id: should not change, and counter should increase
        $this->assertSame('live-3649730296-0', $lis->first()->attr('data-live-id'));
        $this->assertSame('live-3649730296-2', $lis->last()->attr('data-live-id'));

        // fingerprints
        // first and last both have the same input - thus fingerprint
        $this->assertSame('sH/Rwn0x37n3KyMWQLa6OBPgglriBZqlwPLnm/EQTlE=', $lis->first()->attr('data-live-value-fingerprint'));
        $this->assertSame('sH/Rwn0x37n3KyMWQLa6OBPgglriBZqlwPLnm/EQTlE=', $lis->last()->attr('data-live-value-fingerprint'));
        // middle has a different fingerprint
        $this->assertSame('cuOKkrHC9lOmBa6dyVZ3S0REdw4CKCwJgLDdrVoTb2g=', $lis->eq(1)->attr('data-live-value-fingerprint'));
    }
}
