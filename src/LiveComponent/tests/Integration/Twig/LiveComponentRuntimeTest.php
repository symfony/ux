<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Twig\LiveComponentRuntime;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class LiveComponentRuntimeTest extends KernelTestCase
{
    use HasBrowser;

    public function testGetComponentUrl(): void
    {
        $runtime = self::getContainer()->get('ux.live_component.twig.component_runtime');
        \assert($runtime instanceof LiveComponentRuntime);

        $url = $runtime->getComponentUrl('component1', [
            'prop1' => null,
            'prop2' => new \DateTime('2022-10-06-0'),
            'prop3' => 'howdy',
            'attributes' => ['id' => 'in-a-real-scenario-it-would-already-have-one'],
        ]);

        $this->assertStringStartsWith('/_components/component1?props=%7B%22prop1%22:null,%22prop2%22:%222022-10-06T00:00:00%2B00:00%22,%22prop3%22:%22howdy%22,%22', $url);
        $this->browser()
            ->throwExceptions()
            ->get($url)
            ->assertSuccessful()
            ->assertSee('Prop2: 2022-10-06 12:00')
            ->assertSee('Prop3: howdy')
        ;
    }
}
