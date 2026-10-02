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
use Symfony\Component\Mercure\ProtocolVersion;
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
            "{{ turbo_stream_from('a_topic') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic"></turbo-mercure-stream-source>',
        ];

        yield 'string topic — private' => [
            "{{ turbo_stream_from('a_topic', private=true) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" private></turbo-mercure-stream-source>',
        ];

        yield 'class name — single backslash' => [
            "{{ turbo_stream_from('Symfony\\UX\\Turbo\\Tests\\Fixtures\\Book') }}",
            [],
            // single-quoted Twig string: Twig 3 drops the backslash of \X → 'SymfonyUXTurboTestsFixturesBook', Twig 4 keeps it → class_exists → URL pattern
            // @phpstan-ignore greaterOrEqual.alwaysFalse, greaterOrEqual.alwaysTrue (PHPStan only ever sees the installed Twig)
            \Twig\Environment::MAJOR_VERSION >= 4
                ? '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%7Bid%7D"></turbo-mercure-stream-source>'
                : '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=SymfonyUXTurboTestsFixturesBook"></turbo-mercure-stream-source>',
        ];

        yield 'class name — double backslash (correct usage)' => [
            "{{ turbo_stream_from('Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book') }}",
            [],
            // \\\\ in PHP string → \\ in Twig source → \ in Twig output → 'Symfony\UX\Turbo\Tests\Fixtures\Book' → class_exists → URL pattern
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%7Bid%7D"></turbo-mercure-stream-source>',
        ];

        yield 'entity topic' => [
            '{{ turbo_stream_from(book) }}',
            ['book' => $book],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F123"></turbo-mercure-stream-source>',
        ];

        yield 'array of topics' => [
            "{{ turbo_stream_from(['topic_a', 'topic_b']) }}",
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
    }
}
