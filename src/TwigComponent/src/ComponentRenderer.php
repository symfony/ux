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

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\EventListener\PreRenderEvent;
use Twig\Environment;
use Twig\Extension\EscaperExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class ComponentRenderer
{
    private bool $safeClassesRegistered = false;

    public function __construct(
        private Environment $twig,
        private EventDispatcherInterface $dispatcher,
        private ComponentFactory $factory,
        private PropertyAccessorInterface $propertyAccessor
    ) {
    }

    public function render(MountedComponent $mounted): string
    {
        if (!$this->safeClassesRegistered) {
            $this->twig->getExtension(EscaperExtension::class)->addSafeClass(ComponentAttributes::class, ['html']);

            $this->safeClassesRegistered = true;
        }

        $component = $mounted->getComponent();
        $variables = array_merge(
            // add the component as "this"
            ['this' => $component],

            // add computed properties proxy
            ['computed' => new ComputedPropertiesProxy($component)],

            // add attributes
            ['attributes' => $mounted->getAttributes()],

            // expose all public properties
            get_object_vars($component),

            // expose non-public properties marked with ExposeInTemplate attribute
            iterator_to_array($this->exposedVariables($component)),
        );
        $event = new PreRenderEvent($mounted, $this->factory->metadataFor($mounted->getName()), $variables);

        $this->dispatcher->dispatch($event);

        return $this->twig->render($event->getTemplate(), $event->getVariables());
    }

    private function exposedVariables(object $component): \Iterator
    {
        $class = new \ReflectionClass($component);

        foreach ($class->getProperties() as $property) {
            if (!$attribute = $property->getAttributes(ExposeInTemplate::class)[0] ?? null) {
                continue;
            }

            $attribute = $attribute->newInstance();

            /** @var ExposeInTemplate $attribute */
            $value = $attribute->getter ? $component->{rtrim($attribute->getter, '()')}() : $this->propertyAccessor->getValue($component, $property->name);

            yield $attribute->name ?? $property->name => $value;
        }
    }
}
