<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\LiveComponent\Util\LiveComponentStack;
use Symfony\UX\LiveComponent\Util\ModelBindingParser;
use Symfony\UX\TwigComponent\Event\PreMountEvent;

/**
 * Parses the "data-model" key, which triggers extra props to be passed in.
 *
 * For example, data-model: "value:content" would cause a new "value" prop
 * to be passed in set to the parent component's "content" prop.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
final class DataModelPropsSubscriber implements EventSubscriberInterface
{
    private ModelBindingParser $modelBindingParser;

    public function __construct(private LiveComponentStack $componentStack, private PropertyAccessorInterface $propertyAccessor)
    {
        $this->modelBindingParser = new ModelBindingParser();
    }

    public function onPreMount(PreMountEvent $event): void
    {
        $data = $event->getData();
        if (!\array_key_exists('data-model', $data) && !\array_key_exists('dataModel', $data)) {
            return;
        }

        $dataModel = $data['data-model'] ?? $data['dataModel'];
        $bindings = $this->modelBindingParser->parse($dataModel);

        if (0 === \count($bindings)) {
            return;
        }

        // normalize dataModel to a data-model HTML attribute
        unset($data['dataModel']);
        $data['data-model'] = $dataModel;

        // find the first parent of the component about to be rendered that is a Live Component
        // only those can have properties controlled via the data-model attribute
        $parentMountedComponent = $this->componentStack->getCurrentLiveComponent();
        if (null === $parentMountedComponent) {
            throw new \LogicException('You can only pass "data-model" when rendering a component when you\'re rendering inside of a parent component.');
        }

        foreach ($bindings as $binding) {
            $childModel = $binding['child'];
            $parentModel = $binding['parent'];
            $propValue = $this->propertyAccessor->getValue($parentMountedComponent->getComponent(), $parentModel);

            if ('value' === $childModel && \array_key_exists('value', $data)) {
                // Radio or checkbox group: an explicit option value was passed (e.g. value="a").
                // Preserve it and derive the checked state from the parent prop instead.
                $data['checked'] = \is_array($propValue)
                    ? \in_array($data['value'], $propValue, false)
                    : ($data['value'] == $propValue);
            } elseif ('value' === $childModel && isset($data['type']) && 'checkbox' === $data['type']) {
                // Boolean checkbox: type="checkbox" declared at call site but no explicit value.
                // Set checked directly from the boolean prop; do not write a value attribute.
                $data['checked'] = (bool) $propValue;
            } else {
                $data[$childModel] = $propValue;
            }
        }

        $event->setData($data);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreMountEvent::class => 'onPreMount',
        ];
    }
}
