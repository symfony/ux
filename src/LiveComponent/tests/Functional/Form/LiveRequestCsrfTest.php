<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Form;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

/**
 * @see https://github.com/symfony/ux/issues/3865
 */
final class LiveRequestCsrfTest extends WebTestCase
{
    private const CSRF_ERROR = 'The CSRF token is invalid.';

    public function testReRenderAfterDoubleSubmitDoesNotFailCsrf(): void
    {
        $client = $this->createClientAfterDoubleSubmit();

        $this->requestLive($client, '/_components/form_with_collection_type', ['isValidated' => true]);

        $this->assertResponseStatusCodeSame(422);
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('name="blog_post_form[_token]"', $content);
        $this->assertStringContainsString('The title field should not be blank', $content);
        $this->assertStringNotContainsString(self::CSRF_ERROR, $content);
    }

    public function testLiveActionAfterDoubleSubmitDoesNotFailCsrf(): void
    {
        $client = $this->createClientAfterDoubleSubmit();

        $this->requestLive($client, '/_components/form_with_collection_type/save', [
            'blog_post_form.title' => 'Title',
            'blog_post_form.content' => str_repeat('Content ', 20),
            'blog_post_form.comments.0.content' => 'Comment',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString(self::CSRF_ERROR, $client->getResponse()->getContent());
    }

    public function testRegularSubmitStillValidatesCsrf(): void
    {
        $client = static::createClient(['environment' => 'stateless_csrf']);
        $client->request('POST', '/submit-csrf-form', ['form' => ['_token' => 'invalid']], [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Submits a regular form the way csrf_protection_controller.js does: a random token, double-submitted in a cookie.
     */
    private function createClientAfterDoubleSubmit(): KernelBrowser
    {
        $client = static::createClient(['environment' => 'stateless_csrf']);
        $client->request('GET', '/render-template/render_form_with_collection_type');

        $token = base64_encode(random_bytes(18));
        $client->getCookieJar()->set(new Cookie('csrf-token_'.$token, 'csrf-token'));
        $client->request('POST', '/submit-csrf-form', ['form' => ['_token' => $token]], [], ['HTTP_SEC_FETCH_SITE' => 'same-origin']);
        $this->assertResponseIsSuccessful();
        $client->getCookieJar()->expire('csrf-token_'.$token);

        return $client;
    }

    /**
     * Requests the component the way live_controller.js does, with the stateless token placeholder and no double-submit.
     */
    private function requestLive(KernelBrowser $client, string $url, array $updated): void
    {
        $crawler = $client->request('GET', '/render-template/render_form_with_collection_type');
        $props = json_decode($crawler->filter('[data-controller="live"]')->attr('data-live-props-value'), true);
        $this->assertSame('csrf-token', $props['blog_post_form']['_token']);

        $client->request('POST', $url, ['data' => json_encode(['props' => $props, 'updated' => $updated, 'args' => []])], [], [
            'HTTP_ACCEPT' => 'application/vnd.live-component+html',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ]);
    }
}
