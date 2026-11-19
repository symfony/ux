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

use App\Bench\ApacheBench;
use App\Bench\LiveRequest;
use App\Bench\Target;
use App\Bench\TargetProvider;
use PHPUnit\Framework\TestCase;

final class ApacheBenchTest extends TestCase
{
    public function testItParsesAReport(): void
    {
        $report = file_get_contents(__DIR__.'/../Fixtures/ab-report.txt');

        $measurement = new ApacheBench()->parse($report, 200);

        self::assertGreaterThan(0, $measurement->requestsPerSecond);
        self::assertLessThanOrEqual($measurement->p95, $measurement->p50);
        self::assertLessThanOrEqual($measurement->p99, $measurement->p95);
        self::assertSame(0, $measurement->errors);
    }

    public function testNon2xxCountsAsErrorsUnlessExpected(): void
    {
        $report = file_get_contents(__DIR__.'/../Fixtures/ab-report-422.txt');
        $apacheBench = new ApacheBench();

        $unexpected = $apacheBench->parse($report, 200);
        $expected = $apacheBench->parse($report, 422);

        self::assertSame(50, $unexpected->errors);
        self::assertSame(0, $expected->errors);
    }

    public function testItFailsOnAnUnparsableReport(): void
    {
        $apacheBench = new ApacheBench();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not parse the ab report.');

        $apacheBench->parse('nonsense', 200);
    }

    public function testItBuildsTheGetCommand(): void
    {
        $target = $this->target('css/ux-css');
        $url = 'http://127.0.0.1:8180'.$target->path;

        $command = new ApacheBench()->command($url, 4, 15, null);

        self::assertSame(['ab', '-q', '-k', '-c', '4', '-t', '15', '-n', '50000000', 'http://127.0.0.1:8180/css/ux-css'], $command);
    }

    public function testItBuildsThePostCommand(): void
    {
        $request = new LiveRequest('/_components/Live:PostForm', 'a=b', '/live/dashboard');
        $url = 'http://127.0.0.1:8180'.$request->url;

        $command = new ApacheBench()->command($url, 2, null, 200, '/tmp/body', $request->headers());

        self::assertSame([
            'ab', '-q', '-k', '-c', '2', '-n', '200',
            '-p', '/tmp/body', '-T', 'application/x-www-form-urlencoded',
            '-H', 'Accept: application/vnd.live-component+html',
            '-H', 'X-Requested-With: XMLHttpRequest',
            '-H', 'X-Live-Url: /live/dashboard',
            'http://127.0.0.1:8180/_components/Live:PostForm',
        ], $command);
    }

    public function testItNeedsSecondsOrRequests(): void
    {
        $apacheBench = new ApacheBench();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either $seconds or $requests is required.');

        $apacheBench->command('http://127.0.0.1:8180/', 4, null, null);
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
