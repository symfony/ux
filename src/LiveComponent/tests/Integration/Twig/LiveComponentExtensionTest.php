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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentExtensionTest extends KernelTestCase
{
    public function testGetComponentUrl(): void
    {
        $rendered = self::getContainer()->get('twig')->render('component_url.html.twig', [
            'date' => new \DateTime('2022-10-06-0'),
        ]);

        $this->assertStringContainsString('/_components/component1?props=%7B%22prop1%22:null,%22prop2%22:%222022-10-06T00:00:00%2B00:00%22,%22prop3%22:null,', $rendered);
        $this->assertStringContainsString('/alt/alternate_route?', $rendered);
        $this->assertStringContainsString('http://localhost/_components/with_absolute_url?', $rendered);
    }
}
