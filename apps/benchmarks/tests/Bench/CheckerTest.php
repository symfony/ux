<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\Checker;
use App\Bench\LiveRequestBuilder;
use App\Bench\Target;
use App\Bench\TargetProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CheckerTest extends TestCase
{
    private const BASE_URL = 'http://127.0.0.1:8180';

    private string $directory;

    /**
     * @var list<string>
     */
    private array $methods = [];

    protected function setUp(): void
    {
        $this->directory = \sprintf('%s/ux-benchmarks-checker-%s', sys_get_temp_dir(), bin2hex(random_bytes(4)));
        mkdir($this->directory);
        $this->methods = [];
    }

    protected function tearDown(): void
    {
        foreach (glob(\sprintf('%s/*', $this->directory)) ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    public function testAPageAnsweredIdenticallyTwicePasses(): void
    {
        $html = $this->fixture('dashboard.html');
        $target = $this->target('live/props');
        $checker = $this->checker([$this->response($html), $this->response($html)]);

        $stats = $checker->check(self::BASE_URL, [$target], false);

        self::assertCount(1, $stats);
        self::assertSame($target, $stats[0]->target);
    }

    public function testAPageRenderingDifferentHtmlFails(): void
    {
        $html = $this->fixture('dashboard.html');
        $checker = $this->checker([$this->response($html), $this->response(\sprintf('%s ', $html))]);
        $target = $this->target('live/props');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('"live/props" renders a different HTML on two requests.');

        $checker->check(self::BASE_URL, [$target], false);
    }

    public function testAWrongStatusFails(): void
    {
        $checker = $this->checker([$this->response('error', 500)]);
        $target = $this->target('live/props');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('"live/props" answered 500, expected 200.');

        $checker->check(self::BASE_URL, [$target], false);
    }

    public function testAPageDifferentFromThePreviousModeFails(): void
    {
        $html = $this->fixture('dashboard.html');
        $checker = $this->checker([$this->response($html), $this->response($html)]);
        $target = $this->target('live/props');
        file_put_contents($checker->pageFile($target), 'previous mode');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('"live/props" renders a different HTML than the previous mode.');

        $checker->check(self::BASE_URL, [$target], true);
    }

    public function testALiveTargetFetchesItsPageThenPostsTwice(): void
    {
        $page = $this->response($this->fixture('live.html'));
        $checker = $this->checker([$page, $this->response('<div></div>'), $this->response('<div></div>')]);
        $target = $this->target('live/table-search');

        $stats = $checker->check(self::BASE_URL, [$target], false);

        self::assertSame(['GET /live/dashboard', 'POST /_components/Live:PostsTable', 'POST /_components/Live:PostsTable'], $this->methods);
        self::assertSame([], $stats);
        self::assertFileExists($checker->bodyFile($target));
    }

    /**
     * @param list<MockResponse> $responses
     */
    private function checker(array $responses): Checker
    {
        $client = new MockHttpClient(function (string $method, string $url) use (&$responses): MockResponse {
            $this->methods[] = \sprintf('%s %s', $method, substr($url, \strlen(self::BASE_URL)));

            return array_shift($responses);
        });

        return new Checker($client, new LiveRequestBuilder(), $this->directory);
    }

    private function response(string $body, int $status = 200): MockResponse
    {
        return new MockResponse($body, ['http_code' => $status]);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(\sprintf('%s/../Fixtures/%s', __DIR__, $name));
    }

    private function target(string $name): Target
    {
        foreach (new TargetProvider()->targets() as $target) {
            if ($target->name === $name) {
                return $target;
            }
        }

        throw new \LogicException(\sprintf('Unknown target "%s".', $name));
    }
}
