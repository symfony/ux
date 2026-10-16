<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests\Navigation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\UX\Native\EventListener\NativeListener;
use Symfony\UX\Native\Navigation\HistoricalLocation;
use Symfony\UX\Native\Navigation\NativeNavigator;

final class NativeNavigatorTest extends TestCase
{
    public function testIsNative(): void
    {
        self::assertTrue($this->createNavigator($this->createNativeRequest())->isNative());
        self::assertFalse($this->createNavigator($this->createBrowserRequest())->isNative());
        self::assertFalse($this->createNavigator(null)->isNative());
    }

    #[DataProvider('provideLocationsAndPaths')]
    public function testRedirectOrGoesToTheHistoricalLocationInANativeApp(HistoricalLocation $location, string $path): void
    {
        $navigator = $this->createNavigator($this->createNativeRequest());

        self::assertRedirect($path, $navigator->redirectOr($location, '/notes'));
        self::assertRedirect($path, $navigator->redirectOrToRoute($location, 'app_notes'));
        self::assertRedirect($path, $navigator->redirectOrBackOr($location, '/notes'));
        self::assertRedirect($path, $navigator->redirect($location));
    }

    #[DataProvider('provideLocations')]
    public function testRedirectOrGoesToTheTargetInABrowser(HistoricalLocation $location): void
    {
        $request = $this->createBrowserRequest();
        $request->headers->set('Referer', 'https://example.com/notes/1');
        $navigator = $this->createNavigator($request);

        self::assertRedirect('/notes', $navigator->redirectOr($location, '/notes'));
        self::assertRedirect('/notes/2', $navigator->redirectOrToRoute($location, 'app_note', ['id' => 2]));
        self::assertRedirect('https://example.com/notes/1', $navigator->redirectOrBackOr($location, '/notes'));
    }

    public static function provideLocationsAndPaths(): iterable
    {
        yield 'recede' => [HistoricalLocation::Recede, '/recede_historical_location'];
        yield 'refresh' => [HistoricalLocation::Refresh, '/refresh_historical_location'];
        yield 'resume' => [HistoricalLocation::Resume, '/resume_historical_location'];
    }

    public static function provideLocations(): iterable
    {
        foreach (HistoricalLocation::cases() as $location) {
            yield $location->value => [$location];
        }
    }

    #[DataProvider('provideUnsafeReferers')]
    public function testRedirectOrBackOrIgnoresARefererFromAnotherOrigin(?string $referer): void
    {
        $request = $this->createBrowserRequest();
        if (null !== $referer) {
            $request->headers->set('Referer', $referer);
        }

        self::assertRedirect('/notes', $this->createNavigator($request)->redirectOrBackOr(HistoricalLocation::Refresh, '/notes'));
    }

    public static function provideUnsafeReferers(): iterable
    {
        yield 'no referer' => [null];
        yield 'another host' => ['https://evil.example/notes'];
        yield 'a host ending like ours' => ['https://example.com.evil.example/notes'];
        yield 'user info before another host' => ['https://example.com@evil.example/notes'];
        yield 'another scheme' => ['http://example.com/notes'];
        yield 'another port' => ['https://example.com:8443/notes'];
        yield 'scheme-relative' => ['//evil.example/notes'];
        yield 'relative' => ['/notes/1'];
    }

    public function testRedirectOrBackOrFollowsARefererWithAnExplicitDefaultPort(): void
    {
        $request = $this->createBrowserRequest();
        $request->headers->set('Referer', 'https://EXAMPLE.com:443/notes/1');

        self::assertRedirect('https://EXAMPLE.com:443/notes/1', $this->createNavigator($request)->redirectOrBackOr(HistoricalLocation::Refresh, '/notes'));
    }

    public function testRedirectThrowsInABrowser(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot redirect to the "refresh" historical location: the current request does not come from a Hotwire Native app.');

        $this->createNavigator($this->createBrowserRequest())->redirect(HistoricalLocation::Refresh);
    }

    public function testRedirectThrowsWithoutRequest(): void
    {
        $this->expectException(\LogicException::class);

        $this->createNavigator(null)->redirect(HistoricalLocation::Recede);
    }

    private static function assertRedirect(string $expectedUrl, RedirectResponse $response): void
    {
        self::assertSame($expectedUrl, $response->getTargetUrl());
        self::assertSame(303, $response->getStatusCode());
    }

    private function createNavigator(?Request $request): NativeNavigator
    {
        $requestStack = new RequestStack();
        if (null !== $request) {
            $requestStack->push($request);
        }

        $routes = new PhpFileLoader(new FileLocator(__DIR__.'/../../config'))->load('routes.php');
        $routes->add('app_notes', new Route('/notes'));
        $routes->add('app_note', new Route('/notes/{id}'));

        return new NativeNavigator($requestStack, new UrlGenerator($routes, new RequestContext()));
    }

    private function createNativeRequest(): Request
    {
        $request = Request::create('https://example.com/notes/new', 'POST', server: ['HTTP_USER_AGENT' => 'Hotwire Native iOS; Turbo Native iOS; bridge-components: [];']);
        $request->attributes->set(NativeListener::NATIVE_ATTRIBUTE, true);

        return $request;
    }

    private function createBrowserRequest(): Request
    {
        $request = Request::create('https://example.com/notes/new', 'POST');
        $request->attributes->set(NativeListener::NATIVE_ATTRIBUTE, false);

        return $request;
    }
}
