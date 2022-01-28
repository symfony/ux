<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration;

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

        $this->assertStringContainsString('/_components/component1?prop2=2022-10-06T00:00:00', $rendered);
        $this->assertStringContainsString('_checksum=', $rendered);
    }
}
