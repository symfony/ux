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
use Symfony\UX\Turbo\Tests\Fixtures\Book;

final class MercureStreamSourceRendererTest extends KernelTestCase
{
    /**
     * @param array<mixed> $context
     */
    #[DataProvider('provideTestCases')]
    public function testRenderTurboStreamFrom(string $template, array $context, string $expectedResult)
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
            "{{ ux_turbo_stream_from('a_topic') }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic"></turbo-mercure-stream-source>',
        ];

        yield 'string topic — private' => [
            "{{ ux_turbo_stream_from('a_topic', private=true) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=a_topic" private></turbo-mercure-stream-source>',
        ];

        yield 'class name — single backslash (Twig drops \\X)' => [
            "{{ ux_turbo_stream_from('Symfony\\UX\\Turbo\\Tests\\Fixtures\\Book') }}",
            [],
            // single-quoted Twig string: \X drops the backslash → 'SymfonyUXTurboTestsFixturesBook'
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=SymfonyUXTurboTestsFixturesBook"></turbo-mercure-stream-source>',
        ];

        yield 'class name — double backslash (correct usage)' => [
            "{{ ux_turbo_stream_from('Symfony\\\\UX\\\\Turbo\\\\Tests\\\\Fixtures\\\\Book') }}",
            [],
            // \\\\ in PHP string → \\ in Twig source → \ in Twig output → 'Symfony\UX\Turbo\Tests\Fixtures\Book' → class_exists → URL pattern
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F%7Bid%7D"></turbo-mercure-stream-source>',
        ];

        yield 'entity topic' => [
            '{{ ux_turbo_stream_from(book) }}',
            ['book' => $book],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fux-turbo%2FSymfony%255CUX%255CTurbo%255CTests%255CFixtures%255CBook%2F123"></turbo-mercure-stream-source>',
        ];

        yield 'array of topics' => [
            "{{ ux_turbo_stream_from(['topic_a', 'topic_b']) }}",
            [],
            '<turbo-mercure-stream-source src="http://127.0.0.1:3000/.well-known/mercure?topic=topic_a&amp;topic=topic_b"></turbo-mercure-stream-source>',
        ];
    }
}
