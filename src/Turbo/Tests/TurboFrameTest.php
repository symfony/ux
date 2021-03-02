<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\Turbo\Tests;

use Symfony\Component\Panther\PantherTestCase;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class TurboFrameTest extends PantherTestCase
{
    public function testFrame(): void
    {
        ($client = self::createPantherClient())->request('GET', '/');

        $client->clickLink('This block is scoped, the rest of the page will not change if you click here!');
        $client->waitForElementToContain('body', 'This will replace the content of the Turbo Frame!');

        $this->assertTrue(true);
    }
}
