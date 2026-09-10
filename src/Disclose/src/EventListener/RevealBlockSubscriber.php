<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\Disclose\Twig\DiscloseComponent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * Detects an inline "reveal" block authored inside a "<twig:ux:disclose>" tag
 * and signs its location into the disclosure context, so the endpoint can
 * reload that block and render it with the resolved subject.
 *
 * A block written inside a component tag is compiled into the tag body's
 * anonymous embedded module, which the component template never renders: the
 * secret therefore stays out of the initial HTML. This subscriber captures the
 * host template name and the embedded module's deterministic index (both set
 * by ux-twig-component for embedded components) and, when a "reveal" block is
 * present, amends the signed context through
 * {@see DiscloseComponent::enableRevealBlock()}.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class RevealBlockSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Environment $twig) {}

    public function onPreRender(PreRenderEvent $event): void
    {
        $component = $event->getComponent();

        if (!$component instanceof DiscloseComponent) {
            return;
        }

        $mounted = $event->getMountedComponent();

        if (!$mounted->hasExtraMetadata('hostTemplate') || !$mounted->hasExtraMetadata('embeddedTemplateIndex')) {
            return;
        }

        $hostTemplate = $mounted->getExtraMetadata('hostTemplate');
        $embeddedIndex = (int) $mounted->getExtraMetadata('embeddedTemplateIndex');

        // Only engage when the caller actually authored the "reveal" block.
        if (!$this->embeddedTemplate($hostTemplate, $embeddedIndex)->hasBlock('reveal')) {
            return;
        }

        $component->enableRevealBlock($hostTemplate, $embeddedIndex);
    }

    public static function getSubscribedEvents(): array
    {
        return [PreRenderEvent::class => 'onPreRender'];
    }

    private function embeddedTemplate(string $hostTemplate, int $embeddedIndex): TemplateWrapper
    {
        $embedded = $this->twig->loadTemplate(
            $this->twig->getTemplateClass($hostTemplate),
            $hostTemplate,
            $embeddedIndex,
        );

        return new TemplateWrapper($this->twig, $embedded);
    }
}
