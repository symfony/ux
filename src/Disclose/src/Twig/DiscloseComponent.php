<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Twig;

use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Context\DiscloseContextFactory;
use Symfony\UX\Disclose\DiscloseUrlGenerator;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Renders the masked trigger view and the data display view of a protected
 * value. The value itself is never rendered: only the endpoint URL is exposed.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
#[AsTwigComponent('ux:disclose', template: '@Disclose/components/Disclose.html.twig')]
final class DiscloseComponent
{
    /**
     * A disclose context, or any object a subject resolver can resolve
     * (an entity, a document, or a plain value) to build the context from.
     *
     * @var DiscloseContext|object|null
     */
    public ?object $context = null;

    /**
     * Overrides the field to disclose.
     */
    public ?string $field = null;

    /**
     * Application-specific payload carried to the disclosure endpoint.
     *
     * @var array<string, mixed>
     */
    public array $payload = [];

    /**
     * Extra variables passed to the "reveal" block when it is rendered by the
     * disclosure endpoint (in addition to "subject" and "context").
     *
     * @var array<string, mixed>
     */
    public array $vars = [];

    public string $mask = '••••••';

    /**
     * Single-button "toggle" mode: the reveal trigger stays in place, never
     * hides and never gets its content swapped for the loading text. The icon
     * swaps through CSS, and a spinner shows while the request is in flight.
     * Null keeps the legacy two-button behavior (default).
     */
    public ?bool $toggle = null;

    /**
     * Native tooltip shown on hover for the reveal button. Null uses the
     * bundle translation.
     */
    public ?string $title = null;

    /**
     * @var string|null null uses the bundle translation
     */
    public ?string $revealLabel = null;

    public ?string $hideLabel = null;

    public ?string $loadingLabel = null;

    public ?string $errorLabel = null;

    public ?string $rateLimitedLabel = null;

    private ?string $url = null;

    private ?DiscloseContext $builtContext = null;

    /**
     * True when an inline "reveal" block was detected on the component tag and
     * the signed context has been amended to reference it. Set by the bundle
     * PreRenderEvent subscriber after mount.
     */
    private bool $revealBlockEnabled = false;

    private ?DiscloseUrlGenerator $urlGenerator = null;

    private ?DiscloseContextFactory $contextFactory = null;

    #[Required]
    public function setDiscloseUrlGenerator(DiscloseUrlGenerator $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    #[Required]
    public function setDiscloseContextFactory(DiscloseContextFactory $contextFactory): void
    {
        $this->contextFactory = $contextFactory;
    }

    #[PostMount]
    public function normalize(): void
    {
        if (null === $this->context) {
            throw new \LogicException('The "context" property of the "<twig:ux:disclose>" component is required. Pass a disclose context or a managed object.');
        }

        $context = $this->context instanceof DiscloseContext
            ? $this->context
            : $this->contextFactory->createFromObject($this->context, $this->field, $this->payload)
        ;

        if (null !== $this->field && null === $context->field) {
            $context = $context->withField($this->field);
        }

        $extra = $context->getExtra();
        if ($this->payload) {
            $extra = array_merge($extra, $this->payload);
        }

        $this->builtContext = $context->withExtra($extra);
        $this->url = $this->urlGenerator->generate($this->builtContext);
    }

    public function getUrl(): string
    {
        return $this->url ?? '';
    }

    public function getRenderHtml(): bool
    {
        return $this->revealBlockEnabled;
    }

    /**
     * Captures the host template and the embedded module index that own an
     * inline "reveal" block, so the disclosure endpoint can reload that module
     * and render the block with the resolved subject.
     *
     * Called by the bundle PreRenderEvent subscriber after mount, when a
     * "reveal" block was authored inside the component tag. This amends the
     * signed context and regenerates the endpoint URL, so the reference travels
     * in the signed payload.
     */
    public function enableRevealBlock(string $hostTemplate, int $embeddedIndex): void
    {
        if (null === $this->builtContext || null === $this->urlGenerator) {
            return;
        }

        $extra = $this->builtContext->getExtra();
        $extra['template'] = $hostTemplate;
        $extra['embedded_index'] = $embeddedIndex;
        $extra['vars'] = $this->vars;

        $this->builtContext = $this->builtContext->withExtra($extra);
        $this->url = $this->urlGenerator->generate($this->builtContext);
        $this->revealBlockEnabled = true;
    }
}
