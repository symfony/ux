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
 * BroadcastTest.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class BroadcastTest extends PantherTestCase
{
    private const BOOK_TITLE = 'The Ecology of Freedom: The Emergence and Dissolution of Hierarchy';

    public function testBroadcast(): void
    {
        ($client = self::createPantherClient())->request('GET', '/books');
        $crawler = $client->submitForm('Submit', ['title' => self::BOOK_TITLE]);

        $client->waitForElementToContain('#books', self::BOOK_TITLE);

        if (!preg_match('/\(#([0-9]+)\)/', $crawler->filter('#books div')->text(), $matches)) {
            $this->fail('ID not found');
        }

        $client->submitForm('Submit', ['id' => $matches[1], 'title' => 'updated']);
        $client->waitForElementToContain('#books', 'updated');

        $client->submitForm('Submit', ['id' => $matches[1], 'remove' => 'remove']);
        $client->waitForElementToNotContain('#books', $matches[1]);

        $this->assertTrue(true);
    }
}
