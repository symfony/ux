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
class BroadcastTest extends PantherTestCase
{
    private const BOOK_TITLE = 'The Ecology of Freedom: The Emergence and Dissolution of Hierarchy';

    public function testBroadcast(): void
    {
        if (!file_exists(__DIR__.'/app/public/build')) {
            throw new \Exception(sprintf('Move into %s and execute Encore before running this test.', realpath(__DIR__.'/app')));
        }

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
