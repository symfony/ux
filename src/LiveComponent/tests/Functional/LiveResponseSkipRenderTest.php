<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\SkipRenderComponent;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

final class LiveResponseSkipRenderTest extends KernelTestCase
{
    use HasBrowser;
    use InteractsWithLiveComponents;
    use LiveComponentTestHelper;

    protected function setUp(): void
    {
        SkipRenderComponent::$preReRenderCalls = 0;
    }

    public function testSkipRenderAnswersWithOnlyTheRootElementAndTheSkipRenderHeader(): void
    {
        $response = $this->postAction('increment')->client()->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('1', $response->headers->get('X-Live-Skip-Render'));
        $this->assertSame('application/vnd.live-component+html', $response->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression('#^<div data-controller="live" data-live-name-value="skip_render_component" [^>]*></div>$#', $response->getContent());
        $this->assertStringNotContainsString('Count:', $response->getContent());
    }

    public function testSkipRenderSendsTheNewPropsWithAValidChecksum(): void
    {
        $crawler = $this->postAction('increment')->crawler();
        $props = json_decode($crawler->filter('[data-controller="live"]')->attr('data-live-props-value'), true);

        $this->assertSame(1, $props['count']);

        $this->browser()
            ->throwExceptions()
            ->post('/_components/skip_render_component/incrementAndRender', [
                'body' => ['data' => json_encode(['props' => $props])],
            ])
            ->assertStatus(200)
            ->assertSee('Count: 2')
        ;
    }

    public function testSkipRenderCarriesLiveAndBrowserEvents(): void
    {
        $crawler = $this->postAction('incrementWithEvents')->crawler();
        $element = $crawler->filter('[data-controller="live"]');

        $this->assertSame([
            ['event' => 'countIncremented', 'data' => ['count' => 1], 'target' => null, 'componentName' => null],
        ], json_decode($element->attr('data-live-events-to-emit-value'), true));
        $this->assertSame([
            ['event' => 'count:incremented', 'payload' => ['count' => 1]],
        ], json_decode($element->attr('data-live-events-to-dispatch-value'), true));
    }

    public function testSkipRenderStillRunsThePreReRenderHooks(): void
    {
        $this->postAction('increment');

        $this->assertSame(1, SkipRenderComponent::$preReRenderCalls);
    }

    public function testSkipRenderStillUpdatesTheLiveUrl(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('skip_render_component'));

        $this->browser()
            ->throwExceptions()
            ->post('/_components/skip_render_component/increment', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
                'headers' => ['X-Live-Url' => '/'],
            ])
            ->assertStatus(200)
            ->assertHeaderEquals('X-Live-Skip-Render', '1')
            ->assertHeaderEquals('X-Live-Url', '/?count=1')
        ;
    }

    public function testSkipRenderFromTheDefaultActionIsRejected(): void
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('skip_render_from_default_action'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('cannot be returned from the default action');

        $this->browser()
            ->throwExceptions()
            ->post('/_components/skip_render_from_default_action', [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
        ;
    }

    public function testABatchSkipsTheRenderWhenEveryActionAsksForIt(): void
    {
        $browser = $this->postBatch(['increment', 'increment']);
        $response = $browser->client()->getResponse();
        $props = json_decode($browser->crawler()->filter('[data-controller="live"]')->attr('data-live-props-value'), true);

        $this->assertSame('1', $response->headers->get('X-Live-Skip-Render'));
        $this->assertStringNotContainsString('Count:', $response->getContent());
        $this->assertSame(2, $props['count']);
    }

    public function testABatchRendersWhenOneActionDoesNotSkipTheRender(): void
    {
        $this->postBatch(['increment', 'incrementAndRender'])
            ->assertStatus(200)
            ->assertHeaderEquals('X-Live-Skip-Render', null)
            ->assertSee('Count: 2')
        ;
    }

    public function testABatchKeepsAnEarlierDownloadAndRendersWhenALaterActionSkipsTheRender(): void
    {
        $this->postBatch(['incrementAndDownload', 'increment'])
            ->assertStatus(200)
            ->assertHeaderEquals('X-Live-Skip-Render', null)
            ->assertHeaderEquals('X-Live-Download-Url', '/downloads/report.csv')
            ->assertSee('Count: 2')
        ;
    }

    public function testABatchKeepsALaterDownloadAndRendersWhenAnEarlierActionSkipsTheRender(): void
    {
        $this->postBatch(['increment', 'incrementAndDownload'])
            ->assertStatus(200)
            ->assertHeaderEquals('X-Live-Skip-Render', null)
            ->assertHeaderEquals('X-Live-Download-Url', '/downloads/report.csv')
            ->assertSee('Count: 2')
        ;
    }

    public function testTestLiveComponentKeepsWorkingAfterASkippedRender(): void
    {
        $testComponent = $this->createLiveComponent('skip_render_component');

        $testComponent->call('incrementWithEvents');

        $this->assertStringNotContainsString('Count:', $testComponent->render());
        $this->assertSame(1, $testComponent->component()->count);
        $this->assertSame(['count' => 1], $testComponent->getEmittedEvent($testComponent->render(), 'countIncremented')['data']);

        $testComponent->call('incrementAndRender');

        $this->assertStringContainsString('Count: 2', $testComponent->render());
    }

    private function postAction(string $action): KernelBrowser
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('skip_render_component'));

        return $this->browser()
            ->throwExceptions()
            ->post('/_components/skip_render_component/'.$action, [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
        ;
    }

    private function postBatch(array $actions): KernelBrowser
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('skip_render_component'));

        return $this->browser()
            ->throwExceptions()
            ->post('/_components/skip_render_component/_batch', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydrated->getProps(),
                        'actions' => array_map(static fn (string $name): array => ['name' => $name], $actions),
                    ]),
                ],
            ])
        ;
    }
}
