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
}
