<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Controller;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\Disclose\Audit\DiscloseAuditLogger;
use Symfony\UX\Disclose\Audit\DiscloseStatus;
use Symfony\UX\Disclose\Context\DiscloseContextSigner;
use Symfony\UX\Disclose\Context\ExpiredDiscloseContextException;
use Symfony\UX\Disclose\DiscloserRegistry;
use Symfony\UX\Disclose\Event\DiscloseEvent;
use Symfony\UX\Disclose\RateLimiter\DiscloseRateLimiter;
use Symfony\UX\Disclose\Subject\Exception\SubjectNotFoundException;
use Symfony\UX\Disclose\Subject\SubjectResolverRegistry;
use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * Endpoint behind every disclosure: it enforces, in order, the context
 * signature, the rate limit, the subject resolution, the authorization policy
 * and the audit trail before any value is returned.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DiscloseController
{
    public function __construct(
        private readonly DiscloseContextSigner $contextSigner,
        private readonly SubjectResolverRegistry $subjectResolverRegistry,
        private readonly DiscloserRegistry $discloserRegistry,
        private readonly DiscloseRateLimiter $rateLimiter,
        private readonly DiscloseAuditLogger $auditLogger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Environment $twig,
        private readonly ?Security $security = null,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $context = $this->contextSigner->fromRequest($request);
        } catch (ExpiredDiscloseContextException) {
            return $this->fail(410, 'expired', 'DISCLOSE_EXPIRED');
        } catch (BadRequestHttpException) {
            return $this->fail(400, 'invalid_context', 'DISCLOSE_INVALID');
        }

        // Consume a rate-limit token before any database work or authorization
        // check, so every attempt against the endpoint is capped: a denied or
        // not-found request still burns a token, and a burst cannot flood the
        // audit trail or the subject resolver with unbounded requests.
        $identity = $this->rateLimiter->identity($request);

        $rateLimit = $this->rateLimiter->consume($request);
        if (null !== $rateLimit && !$rateLimit->isAccepted()) {
            $retryAfter = $rateLimit->getRetryAfter();
            $seconds = $retryAfter ? max(0, $retryAfter->getTimestamp() - time()) : 1;
            $this->auditLogger->log($context, DiscloseStatus::RateLimited, $identity, ['retry_after' => $seconds]);
            $this->eventDispatcher->dispatch(new DiscloseEvent($context, null, status: DiscloseStatus::RateLimited), DiscloseEvent::REJECTED);

            $response = new JsonResponse([
                'error' => 'rate_limited',
                'code' => 'DISCLOSE_RATE_LIMITED',
                'retry_after' => $seconds,
            ], 429);
            $response->headers->set('Retry-After', $seconds);

            return $response;
        }

        try {
            $subject = $this->subjectResolverRegistry->resolve($context);
        } catch (SubjectNotFoundException) {
            return $this->fail(404, 'subject_not_found', 'DISCLOSE_SUBJECT_NOT_FOUND');
        }

        $discloser = $this->discloserRegistry->getDiscloser($subject);

        if (null === $this->security || !$discloser->isGranted($this->security, $subject, $context)) {
            $this->auditLogger->log($context, DiscloseStatus::AuthDenied, $identity);
            $this->eventDispatcher->dispatch(new DiscloseEvent($context, $subject, status: DiscloseStatus::AuthDenied), DiscloseEvent::REJECTED);

            return $this->fail(403, 'access_denied', 'DISCLOSE_DENIED');
        }

        $this->auditLogger->log($context, DiscloseStatus::Attempt, $identity);
        $this->eventDispatcher->dispatch(new DiscloseEvent($context, $subject, status: DiscloseStatus::Attempt), DiscloseEvent::ATTEMPT);

        $template = $context->get('template');
        $embeddedIndex = $context->get('embedded_index');
        if (\is_string($template) && null !== $embeddedIndex) {
            // Inline "reveal" block mode: the revealed markup lives in the
            // anonymous embedded module compiled from the component tag body.
            // Reload it by its deterministic index and render the "reveal" block
            // with the resolved subject. TemplateWrapper::renderBlock merges the
            // environment globals (Template::renderBlock does not).
            $params = array_merge((array) $context->get('vars'), [
                'subject' => $subject,
                'context' => $context,
            ]);

            $embedded = $this->twig->loadTemplate(
                $this->twig->getTemplateClass($template),
                $template,
                (int) $embeddedIndex,
            );
            $revealed = new TemplateWrapper($this->twig, $embedded)
                ->renderBlock('reveal', $params);
            $data = ['html' => $revealed];
        } else {
            $revealed = $discloser->disclose($subject, $context);
            $data = ['value' => $revealed];
        }

        $this->auditLogger->log($context, DiscloseStatus::Success, $identity);
        $this->eventDispatcher->dispatch(new DiscloseEvent($context, $subject, status: DiscloseStatus::Success), DiscloseEvent::SUCCESS);

        return $this->withNoStore(new JsonResponse($data));
    }

    private function fail(int $status, string $error, string $code): JsonResponse
    {
        return $this->withNoStore(new JsonResponse(['error' => $error, 'code' => $code], $status));
    }

    private function withNoStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
