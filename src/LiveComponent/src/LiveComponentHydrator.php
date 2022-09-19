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
    private const CHECKSUM_KEY = '_checksum';
    private const EXPOSED_PROP_KEY = '_id';
    private const ATTRIBUTES_KEY = '_attributes';

    public function __construct(
        private NormalizerInterface|DenormalizerInterface $normalizer,
        private PropertyAccessorInterface $propertyAccessor,
        private string $secret
    ) {
    }

    public function dehydrate(MountedComponent $mounted): array
    {
        $component = $mounted->getComponent();

        foreach (AsLiveComponent::preDehydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        $data = [];
        $readonlyProperties = [];
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

            if ($liveProp->isReadonly()) {
                $readonlyProperties[] = $frontendName;
            }

            // TODO: improve error message if not readable
            $value = $this->propertyAccessor->getValue($component, $name);
            $dehydratedValue = null;

            if ($method = $liveProp->dehydrateMethod()) {
                // TODO: Error checking
                $dehydratedValue = $component->$method($value);
            } elseif (\is_object($dehydratedValue = $value)) {
                $dehydratedValue = $this->normalizer->normalize($dehydratedValue, 'json', [self::LIVE_CONTEXT => true]);
            }

            if (\count($liveProp->exposed()) > 0) {
                $data[$frontendName] = [
                    self::EXPOSED_PROP_KEY => $dehydratedValue,
                ];

                foreach ($liveProp->exposed() as $propertyPath) {
                    $value = $this->propertyAccessor->getValue($component, sprintf('%s.%s', $name, $propertyPath));
                    $data[$frontendName][$propertyPath] = \is_object($value) ? $this->normalizer->normalize($value, 'json', [self::LIVE_CONTEXT => true]) : $value;
                }
            } else {
                $data[$frontendName] = $dehydratedValue;
            }
        }

        if ($attributes = $mounted->getAttributes()->all()) {
            $data[self::ATTRIBUTES_KEY] = $attributes;
            $readonlyProperties[] = self::ATTRIBUTES_KEY;
        }

        $data[self::CHECKSUM_KEY] = $this->computeChecksum($data, $readonlyProperties);

        return $data;
    }

    public function hydrate(object $component, array $data, string $componentName): MountedComponent
    {
        $readonlyProperties = [];

        if (isset($data[self::ATTRIBUTES_KEY])) {
            $readonlyProperties[] = self::ATTRIBUTES_KEY;
        }

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

        $this->verifyChecksum($data, $readonlyProperties);

        $attributes = new ComponentAttributes($data[self::ATTRIBUTES_KEY] ?? []);

        unset($data[self::CHECKSUM_KEY], $data[self::ATTRIBUTES_KEY]);

        foreach ($propertyContexts as $context) {
            $property = $context->reflectionProperty();
            $liveProp = $context->liveProp();
            $name = $property->getName();
            $frontendName = $liveProp->calculateFieldName($component, $name);

            if (!\array_key_exists($frontendName, $data)) {
                // this property was not sent
                continue;
            }

            $dehydratedValue = $data[$frontendName];

            // if there are exposed keys, then the main value should be hidden
            // in an array under self::EXPOSED_PROP_KEY. But if the value is
            // *not* an array, then use the main value. This could mean that,
            // for example, in a "post.title" situation, the "post" itself was changed.
            if (\count($liveProp->exposed()) > 0 && isset($dehydratedValue[self::EXPOSED_PROP_KEY])) {
                $dehydratedValue = $dehydratedValue[self::EXPOSED_PROP_KEY];
                unset($data[$frontendName][self::EXPOSED_PROP_KEY]);
            }

            $value = $dehydratedValue;
            $type = $property->getType() instanceof \ReflectionNamedType ? $property->getType() : null;

            if ($method = $liveProp->hydrateMethod()) {
                // TODO: Error checking
                $value = $component->$method($dehydratedValue);
            } elseif (!$value && $type && $type->allowsNull() && is_a($type->getName(), \BackedEnum::class, true) && !\in_array($value, array_map(fn (\BackedEnum $e) => $e->value, $type->getName()::cases()))) {
                $value = null;
            } elseif (null !== $value && $type && !$type->isBuiltin()) {
                $value = $this->normalizer->denormalize($value, $type->getName(), 'json', [self::LIVE_CONTEXT => true]);
            }

            foreach ($liveProp->exposed() as $exposedProperty) {
                $propertyPath = self::transformToArrayPath("{$name}.$exposedProperty");

                if (!$this->propertyAccessor->isReadable($data, $propertyPath)) {
                    continue;
                }

                // easy way to read off of the array
                $exposedPropertyData = $this->propertyAccessor->getValue($data, $propertyPath);

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

            // TODO: improve error message if not writable
            $this->propertyAccessor->setValue($component, $name, $value);
        }

        foreach (AsLiveComponent::postHydrateMethods($component) as $method) {
            $component->{$method->name}();
        }

        return new MountedComponent($componentName, $component, $attributes);
    }

    private function computeChecksum(array $data, array $readonlyProperties): string
    {
        // filter to only readonly properties
        $properties = array_filter($data, static fn ($key) => \in_array($key, $readonlyProperties, true), \ARRAY_FILTER_USE_KEY);

        // for read-only properties with "exposed" sub-parts,
        // only use the main value
        foreach ($properties as $key => $val) {
            if (\in_array($key, $readonlyProperties) && \is_array($val) && isset($val[self::EXPOSED_PROP_KEY])) {
                $properties[$key] = $val[self::EXPOSED_PROP_KEY];
            }
        }

        // sort so it is always consistent (frontend could have re-ordered data)
        ksort($properties);

        return base64_encode(hash_hmac('sha256', http_build_query($properties), $this->secret, true));
    }

    private function verifyChecksum(array $data, array $readonlyProperties): void
    {
        if (!\array_key_exists(self::CHECKSUM_KEY, $data)) {
            throw new UnprocessableEntityHttpException('No checksum!');
        }

        if (!hash_equals($this->computeChecksum($data, $readonlyProperties), $data[self::CHECKSUM_KEY])) {
            throw new UnprocessableEntityHttpException('Invalid checksum. This usually means that you tried to change a property that is not writable: true.');
        }
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
