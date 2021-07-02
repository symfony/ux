<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Attribute;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsLiveComponent extends AsTwigComponent
{
    private string $defaultAction;

    public function __construct(string $name, ?string $template = null, string $defaultAction = '__invoke')
    {
        parent::__construct($name, $template);

        $this->defaultAction = trim($defaultAction, '()');
    }

    /**
     * @internal
     *
     * @param string|object $component
     */
    public static function defaultActionFor($component): string
    {
        $component = \is_object($component) ? \get_class($component) : $component;
        $method = self::forClass($component)->defaultAction;

        if (!method_exists($component, $method)) {
            throw new \LogicException(sprintf('Live component "%s" requires the default action method "%s".%s', $component, $method, '__invoke' === $method ? ' Either add this method or use the DefaultActionTrait' : ''));
        }

        return $method;
    }

    /**
     * @internal
     *
     * @return LivePropContext[]
     */
    public static function liveProps(object $component): \Traversable
    {
        $properties = [];

        foreach (self::propertiesFor($component) as $property) {
            if (!$attribute = $property->getAttributes(LiveProp::class)[0] ?? null) {
                continue;
            }

            if (\in_array($property->getName(), $properties, true)) {
                // property name was already used
                continue;
            }

            $properties[] = $property->getName();

            yield new LivePropContext($attribute->newInstance(), $property);
        }
    }

    /**
     * @internal
     */
    public static function isActionAllowed(object $component, string $action): bool
    {
        if (self::defaultActionFor($component) === $action) {
            return true;
        }

        foreach (self::attributeMethodsFor(LiveAction::class, $component) as $method) {
            if ($action === $method->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    public static function beforeReRenderMethods(object $component): \Traversable
    {
        yield from self::attributeMethodsFor(BeforeReRender::class, $component);
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    public static function postHydrateMethods(object $component): \Traversable
    {
        yield from self::attributeMethodsFor(PostHydrate::class, $component);
    }

    /**
     * @internal
     *
     * @return \ReflectionMethod[]
     */
    public static function preDehydrateMethods(object $component): \Traversable
    {
        yield from self::attributeMethodsFor(PreDehydrate::class, $component);
    }

    /**
     * @param string|object $classOrObject
     *
     * @return \ReflectionMethod[]
     */
    private static function attributeMethodsFor(string $attribute, object $component): \Traversable
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes($attribute)[0] ?? null) {
                yield $method;
            }
        }
    }

    /**
     * @return \ReflectionProperty[]
     */
    private static function propertiesFor(object $object): \Traversable
    {
        $class = $object instanceof \ReflectionClass ? $object : new \ReflectionClass($object);

        foreach ($class->getProperties() as $property) {
            yield $property;
        }

        if ($parent = $class->getParentClass()) {
            yield from self::propertiesFor($parent);
        }
    }
}
