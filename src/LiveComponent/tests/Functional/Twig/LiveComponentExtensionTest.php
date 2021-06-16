<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentExtensionTest extends KernelTestCase
{
    use HasBrowser;

    /**
     * @test
     */
    public function init_live_component(): void
    {
        $response = $this->browser()
            ->visit('/render-template/template1')
            ->assertSuccessful()
            ->response()
            ->assertHtml()
        ;

        $div = $response->crawler()->filter('div');
        $data = \json_decode($div->attr('data-live-data-value'), true);

        $this->assertSame('live', $div->attr('data-controller'));
        $this->assertSame('/components/component2', $div->attr('data-live-url-value'));
        $this->assertNotNull($div->attr('data-live-csrf-value'));
        $this->assertCount(2, $data);
        $this->assertSame(1, $data['count']);
        $this->assertArrayHasKey('_checksum', $data);
    }
}
