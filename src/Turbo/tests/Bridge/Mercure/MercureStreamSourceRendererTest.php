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

        yield 'attributes' => [
            "{{ turbo_stream_from('a_topic', transport='legacy', attributes={id: 'a_topic_stream', 'data-turbo-permanent': true, 'data-index': 1, hidden: true, inert: false, title: null}) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" id="a_topic_stream" data-turbo-permanent="true" data-index="1" hidden=""></turbo-mercure-stream-source>',
        ];

        yield 'attributes and private' => [
            "{{ turbo_stream_from('a_topic', private=true, transport='legacy', attributes={id: 'a_topic_stream'}) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" private id="a_topic_stream"></turbo-mercure-stream-source>',
        ];

        yield 'attribute values resolved like html_attr()' => [
            "{{ turbo_stream_from('a_topic', transport='legacy', attributes={'aria-hidden': true, 'aria-busy': false, class: ['a', 'b'], 'data-config': {x: 1}}) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" aria-hidden="true" aria-busy="false" class="a b" data-config="{&quot;x&quot;:1}"></turbo-mercure-stream-source>',
        ];

        yield 'escaped attribute values' => [
            "{{ turbo_stream_from('a_topic', transport='legacy', attributes={'data-label': '\"><script>alert(1)</script>'}) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" data-label="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"></turbo-mercure-stream-source>',
        ];

        yield 'escaped attribute names' => [
            "{{ turbo_stream_from('a_topic', transport='legacy', attributes={'id=\"a\" onclick': 'alert(1)', 'x-on:click': 'open = true'}) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" id&#x3D;&quot;a&quot;&#x20;onclick="alert(1)" x-on:click="open = true"></turbo-mercure-stream-source>',
        ];

        yield 'component' => [
            '<twig:Turbo:Stream:From topics="a_topic" transport="legacy" />',
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic"></turbo-mercure-stream-source>'."\n",
        ];

        yield 'component with attributes' => [
            '<twig:Turbo:Stream:From topics="a_topic" private transport="legacy" id="a_topic_stream" data-turbo-permanent data-testid="{{ name }}" />',
            ['name' => 'notifications'],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" private id="a_topic_stream" data-turbo-permanent="true" data-testid="notifications"></turbo-mercure-stream-source>'."\n",
        ];
    }

    #[DataProvider('provideInvalidAttributes')]
    public function testRenderTurboStreamFromRejectsInvalidAttributes(string $template, string $expectedMessage): void
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(\Twig\Environment::class, $twig);

        try {
            $twig->createTemplate($template)->render();
            $this->fail('An exception should have been thrown.');
        } catch (RuntimeError $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
            $this->assertSame($expectedMessage, $e->getPrevious()->getMessage());
        }
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideInvalidAttributes(): iterable
    {
        yield 'src' => [
            "{{ turbo_stream_from('a_topic', transport='legacy', attributes={src: 'https://example.com'}) }}",
            'The "src" attribute of <turbo-mercure-stream-source> is reserved, it is built from the topics.',
        ];

        yield 'src in the component' => [
            '<twig:Turbo:Stream:From topics="a_topic" transport="legacy" SRC="https://example.com" />',
            'The "SRC" attribute of <turbo-mercure-stream-source> is reserved, it is built from the topics.',
        ];

        yield 'private' => [
            "{{ turbo_stream_from('a_topic', transport='legacy', attributes={private: true}) }}",
            'The "private" attribute of <turbo-mercure-stream-source> is reserved, use the "private" argument of turbo_stream_from() instead.',
        ];

        yield 'list' => [
            "{{ turbo_stream_from('a_topic', transport='legacy', attributes=['hidden']) }}",
            'The attribute name "0" is not valid.',
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
}
