<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Bridge\Mercure;

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Exception\ExceptionInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\UX\Turbo\Bridge\Mercure\MercureStreamSourceRenderer;
use Symfony\UX\Turbo\Bridge\Mercure\TopicResolver;
use Symfony\UX\Turbo\Broadcaster\IdAccessor;
use Symfony\UX\Turbo\Tests\Fixtures\Book;

final class MercureStreamSourceRendererTest extends KernelTestCase
{
    /**
     * @param array<mixed> $context
     */
    #[DataProvider('provideTestCases')]
    public function testRenderTurboStreamFrom(string $template, array $context, string $expectedResult): void
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(\Twig\Environment::class, $twig);

        $this->assertSame($expectedResult, $twig->createTemplate($template)->render($context));
    }

    /**
     * @return iterable<array{0: string, 1: array<mixed>, 2: string}>
     */
    public static function provideTestCases(): iterable
    {
        $book = new Book();
        $book->id = 123;

        yield 'string topic — public' => [
            "{{ turbo_stream_from('a_topic', transport='legacy') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic"></turbo-mercure-stream-source>',
        ];

        yield 'string topic — private' => [
            "{{ turbo_stream_from('a_topic', private=true, transport='legacy') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" private></turbo-mercure-stream-source>',
        ];

        yield 'class name — single backslash' => [
            "{{ turbo_stream_from('Symfony\\UX\\Turbo\\Tests\\Fixtures\\Book', transport='legacy') }}",
            [],
            // single-quoted Twig string: Twig 3 drops the backslash of \X → 'SymfonyUXTurboTestsFixturesBook', Twig 4 keeps it → class_exists → URL pattern
            // @phpstan-ignore greaterOrEqual.alwaysFalse, greaterOrEqual.alwaysTrue (PHPStan only ever sees the installed Twig)
            \Twig\Environment::MAJOR_VERSION >= 4
                ? '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%7Bid%7D"></turbo-mercure-stream-source>'
                : '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=SymfonyUXTurboTestsFixturesBook"></turbo-mercure-stream-source>',
        ];

        yield 'class name — double backslash (correct usage)' => [
            "{{ turbo_stream_from('Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book', transport='legacy') }}",
            [],
            // \\\\ in PHP string → \\ in Twig source → \ in Twig output → 'Symfony\UX\Turbo\Tests\Fixtures\Book' → class_exists → URL pattern
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%7Bid%7D"></turbo-mercure-stream-source>',
        ];

        yield 'entity topic' => [
            "{{ turbo_stream_from(book, transport='legacy') }}",
            ['book' => $book],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F123"></turbo-mercure-stream-source>',
        ];

        yield 'array of topics' => [
            "{{ turbo_stream_from(['topic_a', 'topic_b'], transport='legacy') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=topic_a&amp;topic=topic_b"></turbo-mercure-stream-source>',
        ];
    }

    /**
     * @param array<mixed> $context
     */
    #[DataProvider('provideProtocolV1TestCases')]
    public function testRenderTurboStreamFromWithProtocolV1(string $template, array $context, string $expectedResult): void
    {
        if (!enum_exists(ProtocolVersion::class)) {
            $this->markTestSkipped('The Mercure protocol 1.0 needs symfony/mercure 0.8+.');
        }

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(\Twig\Environment::class, $twig);

        $this->assertSame($expectedResult, $twig->createTemplate($template)->render($context));
    }

    /**
     * @return iterable<string, array{0: string, 1: array<mixed>, 2: string}>
     */
    public static function provideProtocolV1TestCases(): iterable
    {
        $book = new Book();
        $book->id = 123;

        yield 'string topic' => [
            "{{ turbo_stream_from('a_topic', transport='v1') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?match=a_topic"></turbo-mercure-stream-source>',
        ];

        yield 'entity topic' => [
            "{{ turbo_stream_from(book, transport='v1') }}",
            ['book' => $book],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?match=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F123"></turbo-mercure-stream-source>',
        ];

        // URI Templates are gone in the protocol 1.0: every entity of a class is a URL Pattern
        yield 'class name' => [
            "{{ turbo_stream_from('Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book', transport='v1') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?match_urlpattern=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%3Aid"></turbo-mercure-stream-source>',
        ];

        yield 'topics and a class name' => [
            "{{ turbo_stream_from(['topic_a', 'Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book', 'topic_b'], transport='v1') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?match=topic_a&amp;match=topic_b&amp;match_urlpattern=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%3Aid"></turbo-mercure-stream-source>',
        ];

        yield 'private' => [
            "{{ turbo_stream_from('a_topic', private=true, transport='v1') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?match=a_topic" private></turbo-mercure-stream-source>',
        ];

        yield 'private class name' => [
            "{{ turbo_stream_from('Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book', private=true, transport='v1') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?match_urlpattern=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%3Aid" private></turbo-mercure-stream-source>',
        ];
    }

    public function testRenderTurboStreamFromFollowsTheHubProtocol(): void
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(\Twig\Environment::class, $twig);

        $hub = self::getContainer()->get('mercure.hub.default');
        self::assertInstanceOf(HubInterface::class, $hub);

        $this->assertSame(
            TopicResolver::speaksProtocolV1($hub)
                ? '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?match_urlpattern=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%3Aid"></turbo-mercure-stream-source>'
                : '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%7Bid%7D"></turbo-mercure-stream-source>',
            $twig->createTemplate("{{ turbo_stream_from('Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book') }}")->render(),
        );
    }

    public function testRenderTurboStreamFromFailsWhenTheAuthorizationCookieCannotBeCreated(): void
    {
        $renderer = $this->createRenderer('legacy', false);

        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('Unable to create authorization cookie for a hub on the different second-level domain "127.0.0.1".');

        $this->renderInRequest(Request::create('http://localhost/'), static fn (): string => $renderer->render('chat', ['private' => true]));
    }

    public function testRenderTurboStreamFromIgnoresCookieDomainErrors(): void
    {
        $logger = new BufferingLogger();
        $renderer = $this->createRenderer('legacy', true, $logger);

        $html = $this->renderInRequest(Request::create('http://localhost/'), static fn (): string => $renderer->render('chat', ['private' => true]));

        $this->assertSame('', $html);
        $logs = $logger->cleanLogs();
        $this->assertCount(1, $logs);
        $this->assertSame(LogLevel::WARNING, $logs[0][0]);
        $this->assertSame('The private Turbo stream source of the "{hub}" Mercure hub is not rendered, as its authorization cookie cannot be created for the domain of the page: {message}', $logs[0][1]);
        $this->assertSame('legacy', $logs[0][2]['hub']);
        $this->assertSame('Unable to create authorization cookie for a hub on the different second-level domain "127.0.0.1".', $logs[0][2]['message']);
        $this->assertInstanceOf(ExceptionInterface::class, $logs[0][2]['exception']);
    }

    public function testRenderTurboStreamFromDoesNotIgnoreErrorsOfPublicStreamSources(): void
    {
        $renderer = $this->createRenderer('unknown', true, new BufferingLogger());

        $this->expectException(ExceptionInterface::class);

        $renderer->render('chat');
    }

    public function testRenderTurboStreamFromDoesNotIgnoreErrorsBuildingTheUrl(): void
    {
        $renderer = $this->createRenderer('unknown', true, new BufferingLogger());

        $this->expectException(ExceptionInterface::class);

        $this->renderInRequest(Request::create('http://localhost/'), static fn (): string => $renderer->render('chat', ['private' => true]));
    }

    public function testRenderTurboStreamFromDoesNotIgnoreAnAuthorizationCookieSetTwice(): void
    {
        $renderer = $this->createRenderer('legacy', true, new BufferingLogger());

        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('has already been set');

        $this->renderInRequest(Request::create('http://127.0.0.1/'), static fn (): string => $renderer->render('chat', ['private' => true]).$renderer->render('news', ['private' => true]));
    }

    public function testRenderTurboStreamFromDoesNotIgnoreAHubWithoutTokenFactory(): void
    {
        $renderer = $this->createRenderer('default', true, new BufferingLogger());

        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('does not contain a token factory');

        $this->renderInRequest(Request::create('http://localhost/'), static fn (): string => $renderer->render('chat', ['private' => true]));
    }

    private function createRenderer(string $hubName, bool $ignoreCookieDomainErrors, ?LoggerInterface $logger = null): MercureStreamSourceRenderer
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(\Twig\Environment::class, $twig);

        return new MercureStreamSourceRenderer(new IdAccessor(), $twig, $hubName, null, $ignoreCookieDomainErrors, $logger);
    }

    /**
     * @param callable(): string $render
     */
    private function renderInRequest(Request $request, callable $render): string
    {
        $requestStack = self::getContainer()->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);

        $requestStack->push($request);
        try {
            return $render();
        } finally {
            $requestStack->pop();
        }
    }
}
