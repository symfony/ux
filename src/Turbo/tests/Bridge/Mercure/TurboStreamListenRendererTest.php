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

use App\Entity\Book;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;

final class TurboStreamListenRendererTest extends KernelTestCase
{
    /**
     * @dataProvider provideTestCases
     *
     * @param array<mixed> $context
     */
    public function testRenderTurboStreamListen(string $template, array $context, string $expectedResult): void
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
        $newEscape = (new \ReflectionClass(StimulusAttributes::class))->hasMethod('escape');

        $book = new Book();
        $book->id = 123;

        yield [
            "{{ turbo_stream_listen('a_topic') }}",
            [],
            $newEscape
                ? 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http://127.0.0.1:3000/.well-known/mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="a_topic"'
                : 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http&#x3A;&#x2F;&#x2F;127.0.0.1&#x3A;3000&#x2F;.well-known&#x2F;mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="a_topic"',
        ];

        yield [
            "{{ turbo_stream_listen('App\\Entity\\Book') }}",
            [],
            $newEscape
                ? 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http://127.0.0.1:3000/.well-known/mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="AppEntityBook"'
                : 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http&#x3A;&#x2F;&#x2F;127.0.0.1&#x3A;3000&#x2F;.well-known&#x2F;mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="AppEntityBook"',
        ];

        yield [
            '{{ turbo_stream_listen(book) }}',
            ['book' => $book],
            $newEscape
                ? 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http://127.0.0.1:3000/.well-known/mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="https://symfony.com/ux-turbo/App%5CEntity%5CBook/123"'
                : 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http&#x3A;&#x2F;&#x2F;127.0.0.1&#x3A;3000&#x2F;.well-known&#x2F;mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="https&#x3A;&#x2F;&#x2F;symfony.com&#x2F;ux-turbo&#x2F;App&#x25;5CEntity&#x25;5CBook&#x2F;123"',
        ];

        yield [
            "{{ turbo_stream_listen(['a_topic', 'App\\Entity\\Book', book]) }}",
            ['book' => $book],
            $newEscape
                ? 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http://127.0.0.1:3000/.well-known/mercure" data-symfony--ux-turbo--mercure-turbo-stream-topics-value="[&quot;a_topic&quot;,&quot;AppEntityBook&quot;,&quot;https:\/\/symfony.com\/ux-turbo\/App%5CEntity%5CBook\/123&quot;]"'
                : 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http&#x3A;&#x2F;&#x2F;127.0.0.1&#x3A;3000&#x2F;.well-known&#x2F;mercure" data-symfony--ux-turbo--mercure-turbo-stream-topics-value="&#x5B;&quot;a_topic&quot;,&quot;AppEntityBook&quot;,&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;symfony.com&#x5C;&#x2F;ux-turbo&#x5C;&#x2F;App&#x25;5CEntity&#x25;5CBook&#x5C;&#x2F;123&quot;&#x5D;"',
        ];

        yield [
            "{{ turbo_stream_listen('a_topic', 'default', { withCredentials: true }) }}",
            [],
            $newEscape
                ? 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http://127.0.0.1:3000/.well-known/mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="a_topic" data-symfony--ux-turbo--mercure-turbo-stream-with-credentials-value="true"'
                : 'data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http&#x3A;&#x2F;&#x2F;127.0.0.1&#x3A;3000&#x2F;.well-known&#x2F;mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="a_topic" data-symfony--ux-turbo--mercure-turbo-stream-with-credentials-value="true"',
        ];
    }
}
