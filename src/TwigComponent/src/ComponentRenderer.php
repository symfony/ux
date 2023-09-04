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

use Composer\InstalledVersions;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;
use Twig\Extension\EscaperExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentRenderer implements ComponentRendererInterface
{
    private bool $safeClassesRegistered = false;

    public function __construct(
        private Environment $twig,
        private EventDispatcherInterface $dispatcher,
        private ComponentFactory $factory,
        private PropertyAccessorInterface $propertyAccessor,
        private ComponentStack $componentStack
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

        try {
            if (InstalledVersions::getVersion('twig/twig') < 3) {
                return $this->twig->loadTemplate($event->getTemplate(), $event->getTemplateIndex())->render($event->getVariables());
            }

            return $this->twig->loadTemplate(
                $this->twig->getTemplateClass($event->getTemplate()),
                $event->getTemplate(),
                $event->getTemplateIndex(),
            )->render($event->getVariables());
        } finally {
            $this->componentStack->pop();

            $event = new PostRenderEvent($mounted);
            $this->dispatcher->dispatch($event);
        }
    }

    public function embeddedContext(string $name, array $props, array $context, string $hostTemplateName, int $index): array
    {
        $context[PreRenderEvent::EMBEDDED] = true;

        $mounted = $this->factory->create($name, $props);
        $mounted->addExtraMetadata('hostTemplate', $hostTemplateName);
        $mounted->addExtraMetadata('embeddedTemplateIndex', $index);

        $this->componentStack->push($mounted);

        $embeddedContext = $this->preRender($mounted, $context)->getVariables();

        if (!isset($embeddedContext['outerBlocks'])) {
            $embeddedContext['outerBlocks'] = new BlockStack();
        }

        return $embeddedContext;
    }

    public function finishEmbeddedComponentRender(): void
    {
        $this->componentStack->pop();
    }

    private function preRender(MountedComponent $mounted, array $context = []): PreRenderEvent
    {
        if (!$this->safeClassesRegistered) {
            $this->twig->getExtension(EscaperExtension::class)->addSafeClass(ComponentAttributes::class, ['html']);

            $this->safeClassesRegistered = true;
        }

        $component = $mounted->getComponent();
        $metadata = $this->factory->metadataFor($mounted->getName());
        $variables = array_merge(
            // first so values can be overridden
            $context,

            // add the component as "this"
            ['this' => $component],

            // add computed properties proxy
            ['computed' => new ComputedPropertiesProxy($component)],

            // add attributes
            [$metadata->getAttributesVar() => $mounted->getAttributes()],

            // expose public properties and properties marked with ExposeInTemplate attribute
            iterator_to_array($this->exposedVariables($component, $metadata->isPublicPropsExposed())),
        );
        $event = new PreRenderEvent($mounted, $metadata, $variables);

        $this->dispatcher->dispatch($event);

        return $event;
    }

    private function exposedVariables(object $component, bool $exposePublicProps): \Iterator
    {
        if ($exposePublicProps) {
            yield from get_object_vars($component);
        }

        $class = new \ReflectionClass($component);

        foreach ($class->getProperties() as $property) {
            if (!$attribute = $property->getAttributes(ExposeInTemplate::class)[0] ?? null) {
                continue;
            }

            $attribute = $attribute->newInstance();

            /** @var ExposeInTemplate $attribute */
            $value = $attribute->getter ? $component->{rtrim($attribute->getter, '()')}() : $this->propertyAccessor->getValue($component, $property->name);

            if ($attribute->destruct) {
                foreach ($value as $key => $destructedValue) {
                    yield $key => $destructedValue;
                }
            }

            yield $attribute->name ?? $property->name => $value;
        }

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$attribute = $method->getAttributes(ExposeInTemplate::class)[0] ?? null) {
                continue;
            }

            $attribute = $attribute->newInstance();

            /** @var ExposeInTemplate $attribute */
            $name = $attribute->name ?? (str_starts_with($method->name, 'get') ? lcfirst(substr($method->name, 3)) : $method->name);

            if ($method->getNumberOfRequiredParameters()) {
                throw new \LogicException(sprintf('Cannot use %s on methods with required parameters (%s::%s).', ExposeInTemplate::class, $component::class, $method->name));
            }

            if ($attribute->destruct) {
                foreach ($component->{$method->name}() as $prop => $value) {
                    yield $prop => $value;
                }

                return;
            }

            yield $name => $component->{$method->name}();
        }
    }
}
