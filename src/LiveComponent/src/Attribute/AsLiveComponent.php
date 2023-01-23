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
    public function __construct(
        string $name,
        ?string $template = null,
        private ?string $defaultAction = null,
        bool $exposePublicProps = true,
        string $attributesVar = 'attributes',
        public bool $csrf = true,
        public string $route = 'ux_live_component'
    ) {
        parent::__construct($name, $template, $exposePublicProps, $attributesVar);
    }

    /**
     * @internal
     */
    public function serviceConfig(): array
    {
        return array_merge(parent::serviceConfig(), [
            'default_action' => $this->defaultAction,
            'live' => true,
            'csrf' => $this->csrf,
            'route' => $this->route,
        ]);
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
    public static function preReRenderMethods(object $component): \Traversable
    {
        yield from self::attributeMethodsFor(PreReRender::class, $component);
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
