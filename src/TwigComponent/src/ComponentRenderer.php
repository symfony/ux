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
use Twig\Error\LoaderError;
use Twig\Extension\EscaperExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
final class ComponentRenderer
{
    public function __construct(
        private Environment $twig,
        private EventDispatcherInterface $dispatcher,
        private ComponentFactory $factory,
        private PropertyAccessorInterface $propertyAccessor
    ) {
        $this->twig->getExtension(EscaperExtension::class)->addSafeClass(ComponentAttributes::class, ['html']);
    }

    public function render(MountedComponent $mounted): string
    {
        $component = $mounted->getComponent();
        $metadata = $this->factory->metadataFor($mounted->getName());
        $variables = array_merge(
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

        return $this->twig->render($event->getTemplate(), $event->getVariables());
    }

    public function renderAnonymous(string $template, array $data): string
    {
        // TODO configurable attributes - global option for anon components?
        $data['attributes'] = new ComponentAttributes($data['attributes'] ?? []);

        try {
            return $this->twig->render($template, $data);
        } catch (LoaderError) {
            throw new \RuntimeException(); // todo user mistyped the component name, mistyped the component template, didn't autowire component...
        }
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

            yield $attribute->name ?? $property->name => $value;
        }
    }
}
