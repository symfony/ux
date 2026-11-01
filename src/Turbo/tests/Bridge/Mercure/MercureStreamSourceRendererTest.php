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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\UX\Turbo\Bridge\Mercure\TopicResolver;
use Symfony\UX\Turbo\Tests\Fixtures\Book;
use Twig\Error\RuntimeError;

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

        yield 'duplicate topics' => [
            "{{ turbo_stream_from(['topic_a', 'topic_b', 'topic_a'], transport='legacy') }}",
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

    /**
     * @param array<mixed> $context
     * @param list<string> $expectedGrants
     */
    #[DataProvider('provideSubscribeTestCases')]
    public function testRenderTurboStreamFromGrantsTheSubscribedTopics(string $template, array $context, string $expectedResult, array $expectedGrants): void
    {
        [$html, $claims] = $this->renderWithAuthorizationCookie($template, $context, 'legacy');

        $this->assertSame($expectedResult, $html);
        $this->assertSame($expectedGrants, $claims['mercure']['subscribe']);
    }

    /**
     * @return iterable<string, array{0: string, 1: array<mixed>, 2: string, 3: list<string>}>
     */
    public static function provideSubscribeTestCases(): iterable
    {
        $book = new Book();
        $book->id = 123;

        yield 'private' => [
            "{{ turbo_stream_from('chat', private=true, transport='legacy') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=chat" private></turbo-mercure-stream-source>',
            ['chat'],
        ];

        yield 'subscribe' => [
            "{{ turbo_stream_from('chat', private=true, transport='legacy', subscribe=['chat', 'news']) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=chat" private></turbo-mercure-stream-source>',
            ['chat', 'news'],
        ];

        yield 'subscribe to a single topic' => [
            "{{ turbo_stream_from('chat', private=true, transport='legacy', subscribe='news') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=chat" private></turbo-mercure-stream-source>',
            ['chat', 'news'],
        ];

        yield 'subscribe to no topic' => [
            "{{ turbo_stream_from('chat', private=true, transport='legacy', subscribe=[]) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=chat" private></turbo-mercure-stream-source>',
            ['chat'],
        ];

        yield 'subscribe to entities' => [
            "{{ turbo_stream_from('chat', private=true, transport='legacy', subscribe=['Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book', book]) }}",
            ['book' => $book],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=chat" private></turbo-mercure-stream-source>',
            ['chat', 'https://symfony.com/ux-turbo/Symfony%5CUX%5CTurbo%5CTests%5CFixtures%5CBook/{id}', 'https://symfony.com/ux-turbo/Symfony%5CUX%5CTurbo%5CTests%5CFixtures%5CBook/123'],
        ];

        yield 'component' => [
            '<twig:Turbo:Stream:From topics="chat" private transport="legacy" :subscribe="[\'chat\', \'news\']" />',
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=chat" private></turbo-mercure-stream-source>'."\n",
            ['chat', 'news'],
        ];
    }

    /**
     * @param list<array<string, string>> $expectedGrants
     */
    #[DataProvider('provideSubscribeWithProtocolV1TestCases')]
    public function testRenderTurboStreamFromGrantsTheSubscribedTopicsWithProtocolV1(string $template, array $expectedGrants): void
    {
        if (!enum_exists(ProtocolVersion::class)) {
            $this->markTestSkipped('The Mercure protocol 1.0 needs symfony/mercure 0.8+.');
        }

        [$html, $claims] = $this->renderWithAuthorizationCookie($template, [], 'v1');

        $this->assertSame('<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?match=chat" private></turbo-mercure-stream-source>', $html);
        $this->assertSame($expectedGrants, $claims['authorization_details'][0]['topics']);
    }

    /**
     * @return iterable<string, array{0: string, 1: list<array<string, string>>}>
     */
    public static function provideSubscribeWithProtocolV1TestCases(): iterable
    {
        yield 'subscribe' => [
            "{{ turbo_stream_from('chat', private=true, transport='v1', subscribe=['chat', 'news']) }}",
            [['match' => 'chat'], ['match' => 'news']],
        ];

        yield 'subscribe to a class name' => [
            "{{ turbo_stream_from('chat', private=true, transport='v1', subscribe=['chat', 'Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book']) }}",
            [['match' => 'chat'], ['match' => 'https://symfony.com/ux-turbo/Symfony%5CUX%5CTurbo%5CTests%5CFixtures%5CBook/:id', 'match_type' => 'urlpattern']],
        ];
    }

    public function testRenderTurboStreamFromRejectsSubscribeWithoutPrivate(): void
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(\Twig\Environment::class, $twig);

        try {
            $twig->createTemplate("{{ turbo_stream_from('chat', transport='legacy', subscribe=['chat', 'news']) }}")->render();
            $this->fail('An exception should have been thrown.');
        } catch (RuntimeError $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
            $this->assertSame('The "subscribe" option of turbo_stream_from() needs the "private" option.', $e->getPrevious()->getMessage());
        }
    }

    /**
     * @param array<mixed> $context
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function renderWithAuthorizationCookie(string $template, array $context, string $hub): array
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(\Twig\Environment::class, $twig);
        $requestStack = self::getContainer()->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);

        $request = Request::create('http://127.0.0.1/');
        $requestStack->push($request);
        try {
            $html = $twig->createTemplate($template)->render($context);
        } finally {
            $requestStack->pop();
        }

        $cookie = $request->attributes->get('_mercure_authorization_cookies', [])[$hub] ?? null;
        self::assertInstanceOf(Cookie::class, $cookie);

        return [$html, json_decode(base64_decode(strtr(explode('.', (string) $cookie->getValue())[1], '-_', '+/')), true, flags: \JSON_THROW_ON_ERROR)];
    }
}
