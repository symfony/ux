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
class AsLiveComponent extends AsTwigComponent
{
    public function __construct(
        string $name = null,
        string $template = null,
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
     * @param object|class-string $component
     */
    public static function isActionAllowed(object|string $component, string $action): bool
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
     * @param object|class-string $component
     *
     * @return \ReflectionMethod[]
     */
    public static function preReRenderMethods(object|string $component): iterable
    {
        return self::attributeMethodsByPriorityFor($component, PreReRender::class);
    }

    /**
     * @internal
     *
     * @param object|class-string $component
     *
     * @return \ReflectionMethod[]
     */
    public static function postHydrateMethods(object|string $component): iterable
    {
        return self::attributeMethodsByPriorityFor($component, PostHydrate::class);
    }

    /**
     * @internal
     *
     * @param object|class-string $component
     *
     * @return \ReflectionMethod[]
     */
    public static function preDehydrateMethods(object|string $component): iterable
    {
        return self::attributeMethodsByPriorityFor($component, PreDehydrate::class);
    }

    /**
     * @internal
     *
     * @param object|class-string $component
     *
     * @return array<array{action: string, event: string}>
     */
    public static function liveListeners(object|string $component): array
    {
        $listeners = [];
        foreach (self::attributeMethodsFor(LiveListener::class, $component) as $method) {
            foreach ($method->getAttributes(LiveListener::class) as $attribute) {
                $listeners[] = ['action' => $method->getName(), 'event' => $attribute->newInstance()->getEventName()];
            }
        }

        return $listeners;
    }
}
