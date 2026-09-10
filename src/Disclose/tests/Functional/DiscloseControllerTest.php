<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Tests\Fixtures\CollectingTestLogger;
use Symfony\UX\Disclose\Tests\Fixtures\Subject\FixtureData;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DiscloseControllerTest extends WebTestCase
{
    public function testDisclosesTheValueToAnAuthorizedUser(): void
    {
        $client = $this->authenticatedClient();
        $client->request('GET', $this->discloseUrl());

        $response = $client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        self::assertStringContainsString('"value":"the-secret"', $response->getContent());
    }

    public function testRendersAnInlineRevealBlockOnDemand(): void
    {
        $client = $this->authenticatedClient();
        $twig = static::getContainer()->get('twig');

        $html = $twig->render('disclose_reveal_block.html.twig', [
            'context' => DiscloseContext::create(FixtureData::class, '42'),
        ]);

        // The trigger is wired for HTML rendering and carries the signed URL,
        // but the secret never reaches the initial page HTML.
        self::assertStringContainsString('data-disclose-render-html-value="true"', $html);
        self::assertStringContainsString('data-disclose-url-value="', $html);
        self::assertStringNotContainsString('the-secret', $html);

        preg_match('/data-disclose-url-value="([^"]+)"/', $html, $matches);
        self::assertNotEmpty($matches[1], 'The disclose URL is baked into the trigger.');

        // Twig escapes "&" as "&amp;" inside the attribute; decode before requesting.
        $client->request('GET', html_entity_decode($matches[1], \ENT_QUOTES));

        $response = $client->getResponse();
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('<p>Hello the-secret</p>', trim($data['html']));
    }

    public function testDeniesAnonymousUsers(): void
    {
        $client = $this->webClient();
        $client->request('GET', $this->discloseUrl());

        $response = $client->getResponse();
        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('"code":"DISCLOSE_DENIED"', $response->getContent());
    }

    public function testRateLimitsRepeatedDisclosures(): void
    {
        $client = $this->authenticatedClient();

        $client->request('GET', $this->discloseUrl());
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', $this->discloseUrl());
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', $this->discloseUrl());
        $response = $client->getResponse();
        self::assertSame(429, $response->getStatusCode());
        self::assertStringContainsString('"code":"DISCLOSE_RATE_LIMITED"', $response->getContent());
        self::assertStringContainsString('"retry_after":', $response->getContent());
        self::assertNotNull($response->headers->get('Retry-After'));
    }

    public function testRateLimitsDeniedRequests(): void
    {
        // The rate limit guards the endpoint itself, not only successful
        // disclosures: a denied attempt still consumes a token, so a burst of
        // unauthorized requests cannot flood the audit trail unbounded.
        $client = $this->webClient();
        $url = $this->discloseUrl();

        $client->request('GET', $url);
        self::assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('GET', $url);
        self::assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('GET', $url);
        $response = $client->getResponse();
        self::assertSame(429, $response->getStatusCode());
        self::assertStringContainsString('"code":"DISCLOSE_RATE_LIMITED"', $response->getContent());
    }

    public function testRejectsATamperedContext(): void
    {
        $client = static::createClient();
        $url = $this->discloseUrl();

        // Flipping the signature must invalidate the whole request.
        $client->request('GET', $url . '&h=AAAA');

        $response = $client->getResponse();
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('"code":"DISCLOSE_INVALID"', $response->getContent());
    }

    public function testRejectsAnExpiredContext(): void
    {
        $client = static::createClient();
        $client->request('GET', $this->discloseExpiredUrl());

        $response = $client->getResponse();
        self::assertSame(410, $response->getStatusCode());
        self::assertStringContainsString('"code":"DISCLOSE_EXPIRED"', $response->getContent());
    }

    public function testReturnsNotFoundWhenTheSubjectIsMissing(): void
    {
        $client = $this->webClient();
        $client->request('GET', $this->discloseUrlForClass(FixtureData::class, '999'));

        $response = $client->getResponse();
        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('"code":"DISCLOSE_SUBJECT_NOT_FOUND"', $response->getContent());
    }

    public function testReturnsServerErrorWhenTheClassIsNotManaged(): void
    {
        $client = $this->webClient();
        $client->catchExceptions(true);
        $client->request('GET', $this->discloseUrlForClass('App\DoesNotExist'));

        self::assertSame(500, $client->getResponse()->getStatusCode());
    }

    public function testWritesTheAuditTrailOnSuccess(): void
    {
        $client = $this->authenticatedClient();
        $client->request('GET', $this->discloseUrl());

        /** @var CollectingTestLogger $logger */
        $logger = static::getContainer()->get('test.logger');
        $successRecords = array_values(array_filter(
            $logger->getRecords(),
            static fn(array $record): bool => str_contains($record['message'], 'Protected value disclosure: success'),
        ));

        self::assertCount(1, $successRecords);
        self::assertSame(FixtureData::class, $successRecords[0]['context']['context']['class']);
        self::assertStringStartsWith('test-', $successRecords[0]['context']['identity']);
    }

    private function authenticatedClient(): KernelBrowser
    {
        $client = $this->webClient();
        $client->setServerParameter('PHP_AUTH_USER', 'mr_discloser');
        $client->setServerParameter('PHP_AUTH_PW', 'symfonypass');

        return $client;
    }

    private function webClient(): KernelBrowser
    {
        $client = static::createClient();
        // The rate limiter cache persists across tests and even across runs, so
        // give every test an isolated budget to avoid spending another test's
        // tokens now that denied and not-found attempts consume one too.
        $client->setServerParameter('UX_DISCLOSE_SUBJECT', 'test-' . bin2hex(random_bytes(4)));

        return $client;
    }

    private function discloseUrl(): string
    {
        return $this->discloseUrlForClass(FixtureData::class);
    }

    private function discloseUrlForClass(string $class, string $id = '42'): string
    {
        return static::getContainer()
            ->get('ux.disclose.url_generator')
            ->generate(DiscloseContext::create($class, $id, 'secret'))
        ;
    }

    private function discloseExpiredUrl(): string
    {
        // Sign a context that already expired one second ago. The signer is the
        // only seam that lets a test bake a past expiry into a valid signature.
        $params = static::getContainer()
            ->get('ux.disclose.context_signer')
            ->sign(DiscloseContext::create(FixtureData::class, '42', 'secret'), time() - 1)
        ;

        return static::getContainer()->get('router')->generate('ux_disclose', $params);
    }
}
