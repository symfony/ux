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
 * BroadcastTest.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @requires PHP 8.0
 */
class BroadcastTest extends PantherTestCase
{
    private const BOOK_TITLE = 'The Ecology of Freedom: The Emergence and Dissolution of Hierarchy';

    public function testBroadcast(): void
    {
        ($client = self::createPantherClient())->request('GET', '/books');

        $crawler = $client->submitForm('Submit', ['title' => self::BOOK_TITLE]);

        $this->assertSelectorWillContain('#books', self::BOOK_TITLE);
        if (!preg_match('/\(#(\d+)\)/', $crawler->filter('#books div')->text(), $matches)) {
            $this->fail('ID not found');
        }

        $client->submitForm('Submit', ['id' => $matches[1], 'title' => 'updated']);
        $this->assertSelectorWillContain('#books', 'updated');

        $client->submitForm('Submit', ['id' => $matches[1], 'remove' => 'remove']);
        $this->assertSelectorWillNotContain('#books', $matches[1]);
    }
}
