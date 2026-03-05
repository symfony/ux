<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentRenderer implements ComponentRendererInterface, ResetInterface
{
    private array $templateClasses = [];

    public function __construct(
        private Environment $twig,
        private EventDispatcherInterface $dispatcher,
        private ComponentFactory $factory,
        private ComponentProperties $componentProperties,
        private ComponentStack $componentStack,
    ) {
    }

    /**
     * Allow the render process to be short-circuited.
     */
    public function preCreateForRender(string $name, array $props = []): ?string
    {
        $event = new PreCreateForRenderEvent($name, $props);
        $this->dispatcher->dispatch($event);

        return $event->getRenderedString();
    }

    public function createAndRender(string $name, array $props = []): string
    {
        if ($preRendered = $this->preCreateForRender($name, $props)) {
            return $preRendered;
        }

        return $this->render($this->factory->create($name, $props));
    }

    public function render(MountedComponent $mounted): string
    {
        $this->componentStack->push($mounted);

        $event = $this->preRender($mounted);

        $variables = $event->getVariables();
        // see ComponentNode. When rendering an individual embedded component,
        // *not* through its parent, we need to set the parent template.
        if ($templateIndex = $event->getTemplateIndex()) {
            $variables['__parent__'] = $event->getParentTemplateForEmbedded();
        }

        try {
            return $this->twig->loadTemplate(
                $this->templateClasses[$template = $event->getTemplate()] ??= $this->twig->getTemplateClass($template),
                $template,
                $templateIndex,
            )->render($variables);
        } finally {
            $mounted = $this->componentStack->pop();

            $event = new PostRenderEvent($mounted);
            $this->dispatcher->dispatch($event);
        }
    }

    public function startEmbeddedComponentRender(string $name, array $props, array $context, string $hostTemplateName, int $index): PreRenderEvent
    {
        $context[PreRenderEvent::EMBEDDED] = true;

        $mounted = $this->factory->create($name, $props);
        $mounted->addExtraMetadata('hostTemplate', $hostTemplateName);
        $mounted->addExtraMetadata('embeddedTemplateIndex', $index);

        $this->componentStack->push($mounted);

        return $this->preRender($mounted, $context);
    }

    public function finishEmbeddedComponentRender(): void
    {
        $mounted = $this->componentStack->pop();

        $event = new PostRenderEvent($mounted);
        $this->dispatcher->dispatch($event);
    }

    private function preRender(MountedComponent $mounted, array $context = []): PreRenderEvent
    {
        $component = $mounted->getComponent();
        $metadata = $this->factory->metadataFor($mounted->getName());

        $classProps = [];
        if (!$metadata->isAnonymous()) {
            $classProps = $this->componentProperties->getProperties($component, $metadata->isPublicPropsExposed());
        }

        // expose public properties and properties marked with ExposeInTemplate attribute
        $props = [...$mounted->getInputProps(), ...$classProps];
        $event = new PreRenderEvent($mounted, $metadata, [
            ...$context,
            ...$props,
            $metadata->getAttributesVar() => $mounted->getAttributes(),
        ]);

        $this->dispatcher->dispatch($event);

        $event->setVariables([
            ...$event->getVariables(),
            // add the component as "this"
            'this' => $component,
            'computed' => new ComputedPropertiesProxy($component),
            'outerScope' => $context,
            // keep this line for BC break reasons
            '__props' => $classProps,
            // add the context in a separate variable to keep track
            // of what is coming from outside the component, excluding props
            // as they override initial context values
            '__context' => array_diff_key($context, $props),
        ]);

        return $event;
    }

    public function reset(): void
    {
        $this->templateClasses = [];
    }
}
