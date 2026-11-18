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

use App\Bench\LiveRequestBuilder;
use App\Bench\LiveScenario;
use PHPUnit\Framework\TestCase;

final class LiveRequestBuilderTest extends TestCase
{
    public function testItBuildsAnUpdatedBodyWithChildren(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/live.html');
        $scenario = new LiveScenario(page: '/live/dashboard', component: 'Live:PostsTable', updated: ['query' => 'symfony']);

        $request = new LiveRequestBuilder()->build($html, $scenario);

        parse_str($request->body, $form);
        $data = json_decode($form['data'], true);
        self::assertSame('/_components/Live:PostsTable', $request->url);
        self::assertSame('abc', $data['props']['@checksum']);
        self::assertSame(['query' => 'symfony'], $data['updated']);
        self::assertSame([
            'featured-1' => ['fingerprint' => 'f1', 'tag' => 'div'],
            'featured-2' => ['fingerprint' => 'f2', 'tag' => 'div'],
        ], $data['children']);
        self::assertArrayNotHasKey('args', $data);
        self::assertSame('/live/dashboard', $request->pageUrl);
    }

    public function testItAppendsTheActionAndArgs(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/live.html');
        $scenario = new LiveScenario(page: '/live/dashboard', component: 'Live:PostsTable', action: 'goToPage', args: ['page' => 2]);

        $request = new LiveRequestBuilder()->build($html, $scenario);

        parse_str($request->body, $form);
        $data = json_decode($form['data'], true);
        self::assertStringEndsWith('/goToPage', $request->url);
        self::assertSame(['page' => 2], $data['args']);
    }

    public function testItSelectsTheFirstRows(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/live.html');
        $scenario = new LiveScenario(page: '/live/dashboard', component: 'Live:PostsTable', selectRows: 1);

        $request = new LiveRequestBuilder()->build($html, $scenario);

        parse_str($request->body, $form);
        $data = json_decode($form['data'], true);
        self::assertSame(['1'], $data['updated']['selected']);
    }

    public function testItFailsWhenTheComponentIsMissing(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/live.html');
        $scenario = new LiveScenario(page: '/live/dashboard', component: 'Live:Missing');
        $builder = new LiveRequestBuilder();

        $this->expectException(\RuntimeException::class);

        $builder->build($html, $scenario);
    }
}
