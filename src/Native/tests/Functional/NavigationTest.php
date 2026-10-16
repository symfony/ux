<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\Native\Tests\Fixtures\Navigation\NavigationKernel;

final class NavigationTest extends WebTestCase
{
    private const NATIVE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Hotwire Native iOS; Turbo Native iOS; bridge-components: [];';

    protected static function getKernelClass(): string
    {
        return NavigationKernel::class;
    }

    #[DataProvider('provideHistoricalLocations')]
    public function testHistoricalLocationRespondsWithoutBeingIndexed(string $path, string $message): void
    {
        $client = self::createClient();
        $client->request('GET', $path);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex');
        self::assertStringContainsString('<p>'.$message.'</p>', $client->getResponse()->getContent());
    }

    public static function provideHistoricalLocations(): iterable
    {
        yield 'recede' => ['/recede_historical_location', 'Going back…'];
        yield 'refresh' => ['/refresh_historical_location', 'Refreshing…'];
        yield 'resume' => ['/resume_historical_location', 'Staying put…'];
    }

    public function testNativeAppIsSentToTheHistoricalLocationAndKeepsTheFlash(): void
    {
        $client = self::createClient([], ['HTTP_USER_AGENT' => self::NATIVE_USER_AGENT]);
        $client->request('POST', '/notes/new');

        self::assertResponseRedirects('/refresh_historical_location', 303);

        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $client->request('GET', '/notes');
        self::assertSame('Note created.', $client->getResponse()->getContent());
    }

    public function testBrowserIsSentToTheTargetAndKeepsTheFlash(): void
    {
        $client = self::createClient();
        $client->request('POST', '/notes/new');

        self::assertResponseRedirects('/notes', 303);

        $client->followRedirect();
        self::assertSame('Note created.', $client->getResponse()->getContent());
    }
}
