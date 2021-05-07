<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $this->assertSelectorWillContain('body', 'This will replace the content of the Turbo Frame!');
    }
}
