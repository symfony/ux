<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RedirectUrlTest extends WebTestCase
{
    /**
     * @dataProvider getRedirectionTests
     */
    public function testUrlRedirections(string $url, string $expectedUrl, int $expectedStatusCode)
    {
        $client = self::createClient();
        $client->request('GET', $url);
        $this->assertResponseRedirects($expectedUrl, $expectedStatusCode);
    }

    protected static function getRedirectionTests(): array
    {
        return [
            // LiveComponent Demos
            ['/live-component/demos/auto-validating-form', '/demos/live-component/auto-validating-form', 301],
            ['/live-component/demos/chartjs', '/demos/live-component/chartjs', 301],
            ['/live-component/demos/dependent-form-fields', '/demos/live-component/dependent-form-fields', 301],
            ['/live-component/demos/form-collection-type', '/demos/live-component/form-collection-type', 301],
            ['/live-component/demos/inline-edit', '/demos/live-component/inline-edit', 301],
            ['/live-component/demos/invoice', '/demos/live-component/invoice', 301],
            ['/live-component/demos/product-form', '/demos/live-component/product-form', 301],
            ['/live-component/demos/upload', '/demos/live-component/upload', 301],
            ['/live-component/demos/voting', '/demos/live-component/voting', 301],
            ['/live-component/demos', '/demos', 302],
        ];
    }
}
