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
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\RemoveComponent;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class LiveResponseRemoveTest extends KernelTestCase
{
    use HasBrowser;
    use LiveComponentTestHelper;

    protected function setUp(): void
    {
        RemoveComponent::$preReRenderCalls = 0;
    }

    public function testRemoveAnswersWithRenderedHtmlAndTheRemoveHeader()
    {
        $response = $this->postAction('dismiss')->client()->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('1', $response->headers->get('X-Live-Remove'));
        $this->assertSame('application/vnd.live-component+html', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Count: 1', $response->getContent());
    }

    public function testRemoveRendersAndRunsTheHooks()
    {
        $this->postAction('dismiss');

        $this->assertSame(1, RemoveComponent::$preReRenderCalls);
    }

    public function testRemoveCarriesLiveAndBrowserEventsInTheRenderedHtml()
    {
        $crawler = $this->postAction('dismissWithEvents')->crawler();
        $element = $crawler->filter('[data-controller~="live"]');

        $this->assertSame([
            ['event' => 'componentRemoved', 'data' => ['id' => 42], 'target' => null, 'componentName' => null],
        ], json_decode($element->attr('data-live-events-to-emit-value'), true));
        $this->assertSame([
            ['event' => 'component:removed', 'payload' => ['id' => 42]],
        ], json_decode($element->attr('data-live-events-to-dispatch-value'), true));
    }

    public function testAnOrdinaryActionStillRendersAndRunsTheHooks()
    {
        $response = $this->postAction('keep')->client()->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-Live-Remove'));
        $this->assertStringContainsString('Count: 1', $response->getContent());
        $this->assertSame(1, RemoveComponent::$preReRenderCalls);
    }

    private function postAction(string $action): object
    {
        $dehydrated = $this->dehydrateComponent($this->mountComponent('remove_component'));

        return $this->browser()
            ->throwExceptions()
            ->post('/_components/remove_component/'.$action, [
                'body' => ['data' => json_encode(['props' => $dehydrated->getProps()])],
            ])
        ;
    }
}
