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

use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\PantherTestCase;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class BroadcastTest extends PantherTestCase
{
    private const BOOK_TITLE = 'The Ecology of Freedom: The Emergence and Dissolution of Hierarchy';
    private const SONG_TITLE = 'Bohemian Rhapsody';
    private const ARTIST_NAME_1 = 'Queen';
    private const ARTIST_NAME_2 = 'Elvis Presley';

    protected function setUp(): void
    {
        if (!file_exists(__DIR__.'/app/assets/vendor/installed.php')) {
            throw new \Exception(\sprintf('Move into "%s" and execute `php public/index.php importmap:install` before running this test.', realpath(__DIR__.'/app')));
        }

        parent::setUp();
    }

    public function testBroadcastBasic(): void
    {
        ($client = self::createPantherClient())->request('GET', '/books');

        $crawler = $client->submitForm('Submit', ['title' => self::BOOK_TITLE]);
        // $client->waitForElementToContain('#books div', self::BOOK_TITLE);

        $this->assertSelectorWillContain('#books', self::BOOK_TITLE);
        if (!preg_match('/\(#(\d+)\)/', $crawler->filter('#books div')->text(), $matches)) {
            $this->fail('ID not found');
        }

        $client->submitForm('Submit', ['id' => $matches[1], 'title' => 'updated']);
        $this->assertSelectorWillContain('#books', 'updated');

        $client->submitForm('Submit', ['id' => $matches[1], 'remove' => 'remove']);
        $this->assertSelectorWillNotContain('#books', $matches[1]);
    }

    public function testExpressionLanguageBroadcast(): void
    {
        ($client = self::createPantherClient())->request('GET', '/artists');

        $client->submitForm('Submit', ['name' => self::ARTIST_NAME_1]);
        $client->waitForElementToContain('#artists div:nth-child(1)', self::ARTIST_NAME_1, 5);

        $client->submitForm('Submit', ['name' => self::ARTIST_NAME_2]);
        $client->waitForElementToContain('#artists div:nth-child(2)', self::ARTIST_NAME_2, 5);

        $crawlerArtist = $client->getCrawler();

        $this->assertSelectorWillContain('#artists', self::ARTIST_NAME_1);
        if (!preg_match_all('/\(#(\d+)\)/', $crawlerArtist->filter('#artists')->text(), $matches)) {
            $this->fail('IDs of artists not found');
        }

        $artist1Id = $matches[1][0];
        $artist2Id = $matches[1][1];

        ($clientArtist1 = self::createAdditionalPantherClient())->request('GET', '/artists/'.$artist1Id);
        ($clientArtist2 = self::createAdditionalPantherClient())->request('GET', '/artists/'.$artist2Id);

        $client->request('GET', '/songs');

        $client->submitForm('Submit', ['title' => self::SONG_TITLE, 'artistId' => $artist1Id]);

        $clientArtist1->waitForElementToContain('#songs div', self::SONG_TITLE, 5);

        $songsElement = $clientArtist2->findElement(WebDriverBy::cssSelector('#songs'));

        $this->assertStringNotContainsString(
            self::SONG_TITLE,
            $songsElement->getText(),
            'Artist 2 shows a song that does not belong to them'
        );
    }

    public function testBroadcastWithProxy(): void
    {
        // testing that Artist is updated, even though it's saved as Proxy
        ($client = self::createPantherClient())->request('GET', '/artistFromSong');

        // submit first time to create the artist
        $client->submitForm('Submit');
        $this->assertSelectorWillContain('#artists', 'testing artist');

        // submit again to update the artist
        $client->submitForm('Submit');
        $this->assertSelectorWillContain('#artists', 'testing artist after change');
        // this part is from the stream template
        $this->assertSelectorWillContain('#artists', ', updated)');
    }
}
