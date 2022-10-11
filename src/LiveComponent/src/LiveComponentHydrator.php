<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LivePropContext;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class LiveComponentHydrator
{
    public const LIVE_CONTEXT = 'live-component';

    public function __construct(
        private NormalizerInterface|DenormalizerInterface $normalizer,
        private PropertyAccessorInterface $propertyAccessor,
        private string $secret
    ) {
    }

    public function dehydrate(MountedComponent $mounted): DehydratedComponent
    {
        $component = $mounted->getComponent();

        foreach (AsLiveComponent::preDehydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        $props = [];
        $data = [];
        $exposedData = [];
        $frontendPropertyNames = [];

        foreach (AsLiveComponent::liveProps($component) as $context) {
            $property = $context->reflectionProperty();
            $liveProp = $context->liveProp();
            $name = $property->getName();
            $frontendName = $liveProp->calculateFieldName($component, $property->getName());

            if (isset($frontendPropertyNames[$frontendName])) {
                $message = sprintf('The field name "%s" cannot be used by multiple LiveProp properties in a component. Currently, both "%s" and "%s" are trying to use it in "%s".', $frontendName, $frontendPropertyNames[$frontendName], $name, \get_class($component));

                if ($frontendName === $frontendPropertyNames[$frontendName] || $frontendName === $name) {
                    $message .= sprintf(' Try adding LiveProp(fieldName="somethingElse") for the "%s" property to avoid this.', $frontendName);
                }

                throw new \LogicException($message);
            }

            $frontendPropertyNames[$frontendName] = $name;

            // TODO: improve error message if not readable
            $value = $this->propertyAccessor->getValue($component, $name);
            $dehydratedValue = null;

            if ($method = $liveProp->dehydrateMethod()) {
                // TODO: Error checking
                $dehydratedValue = $component->$method($value);
            } elseif (\is_object($dehydratedValue = $value)) {
                $dehydratedValue = $this->normalizer->normalize($dehydratedValue, 'json', [self::LIVE_CONTEXT => true]);
            }

            if ($liveProp->isReadonly()) {
                $props[$frontendName] = $dehydratedValue;
            } else {
                $data[$frontendName] = $dehydratedValue;
            }

            // exposed properties are currently always writable, so data
            if ($liveProp->exposed()) {
                $exposedData[$frontendName] = [];
                foreach ($liveProp->exposed() as $propertyPath) {
                    $value = $this->propertyAccessor->getValue($component, sprintf('%s.%s', $name, $propertyPath));
                    $exposedData[$frontendName][$propertyPath] = \is_object($value) ? $this->normalizer->normalize($value, 'json', [self::LIVE_CONTEXT => true]) : $value;
                }
            }
        }

        $attributes = $mounted->getAttributes()->all();

        return new DehydratedComponent($props, $data, $exposedData, $attributes, $this->secret);
    }

    public function hydrate(object $component, array $data, string $componentName): MountedComponent
    {
        $readonlyProperties = [];

        /** @var LivePropContext[] $propertyContexts */
        $propertyContexts = iterator_to_array(AsLiveComponent::liveProps($component));

        /*
         * Determine readonly properties for checksum verification. We need to do this
         * before setting properties on the component. It is unlikely but there could
         * be security implications to doing it after (component setter's could have
         * side effects).
         */
        foreach ($propertyContexts as $context) {
            $liveProp = $context->liveProp();
            $name = $context->reflectionProperty()->getName();

            if ($liveProp->isReadonly()) {
                $readonlyProperties[] = $liveProp->calculateFieldName($component, $name);
            }
        }
        $dehydratedComponent = DehydratedComponent::createFromCombinedData($data, $readonlyProperties, $this->secret);

        if (!$dehydratedComponent->isChecksumValid($data)) {
            throw new UnprocessableEntityHttpException('Invalid or missing checksum. This usually means that you tried to change a property that is not writable: true.');
        }

        $attributes = new ComponentAttributes($dehydratedComponent->getAttributes());

        foreach ($propertyContexts as $context) {
            $property = $context->reflectionProperty();
            $liveProp = $context->liveProp();
            $name = $property->getName();
            $frontendName = $liveProp->calculateFieldName($component, $name);

            if (!$dehydratedComponent->has($frontendName)) {
                // this property was not sent
                continue;
            }

            $value = $dehydratedComponent->get($frontendName);
            $type = $property->getType() instanceof \ReflectionNamedType ? $property->getType() : null;

            if ($method = $liveProp->hydrateMethod()) {
                // TODO: Error checking
                $value = $component->$method($value);
            } elseif (!$value && $type && $type->allowsNull() && is_a($type->getName(), \BackedEnum::class, true) && !\in_array($value, array_map(fn (\BackedEnum $e) => $e->value, $type->getName()::cases()))) {
                $value = null;
            } elseif (null !== $value && $type && !$type->isBuiltin()) {
                $value = $this->normalizer->denormalize($value, $type->getName(), 'json', [self::LIVE_CONTEXT => true]);
            }

            if ($dehydratedComponent->hasExposed($frontendName)) {
                $exposedData = $dehydratedComponent->getExposed($frontendName);
                foreach ($liveProp->exposed() as $exposedProperty) {
                    $propertyPath = self::transformToArrayPath($exposedProperty);

                    if (!$this->propertyAccessor->isReadable($exposedData, $propertyPath)) {
                        continue;
                    }

                    // easy way to read off of the array
                    $exposedPropertyData = $this->propertyAccessor->getValue($exposedData, $propertyPath);

                    try {
                        $this->propertyAccessor->setValue(
                            $value,
                            $exposedProperty,
                            $exposedPropertyData
                        );
                    } catch (UnexpectedTypeException $e) {
                        throw new \LogicException(sprintf('Unable to set the exposed field "%s" onto the "%s" property because it has an invalid type (%s).', $exposedProperty, $name, get_debug_type($value)), 0, $e);
                    }
                }
            }

            // TODO: improve error message if not writable
            $this->propertyAccessor->setValue($component, $name, $value);
        }

        foreach (AsLiveComponent::postHydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        return new MountedComponent($componentName, $component, $attributes);
    }

    /**
     * Transforms a path like `post.name` into `[post][name]`.
     *
     * This allows us to use the property accessor to find this
     * inside an array.
     */
    private static function transformToArrayPath(string $propertyPath): string
    {
        $parts = explode('.', $propertyPath);
        $path = '';

        foreach ($parts as $part) {
            $path .= "[{$part}]";
        }

        return $path;
    }
}
