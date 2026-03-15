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

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\FromMethod;

/**
 * An attribute to register a LiveComponent.
 *
 * @see https://symfony.com/bundles/ux-live-component
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsLiveComponent extends AsTwigComponent
{
    public string $route;
    public string $method;
    public int $urlReferenceType;
    public ?string $fetchCredentials;

    private ?string $defaultAction;

    /**
     * @param string|null              $name              The component name (ie: TodoList)
     * @param string|FromMethod|null   $template          The template path of the component (ie: components/TodoList.html.twig), or a reference to a component's method (ie: FromMethod('getTemplate')
     * @param string|null              $defaultAction     The default action to call when the component is mounted (ie: __invoke)
     * @param bool                     $exposePublicProps Whether to expose every public property as a Twig variable
     * @param string                   $attributesVar     The name of the special "attributes" variable in the template
     * @param string                   $route             The route used to render the component & handle actions
     * @param string                   $method            The HTTP method to use
     * @param UrlGeneratorInterface::* $urlReferenceType  Which type of URL should be generated for the given route
     * @param string|null              $fetchCredentials  The fetch credentials mode to use ('same-origin', 'include', 'omit'), null to use the global default
     */
    public function __construct(
        ?string $name = null,
        string|FromMethod|null $template = null,
        ?string $defaultAction = null,
        bool $exposePublicProps = true,
        string $attributesVar = 'attributes',
        string $route = 'ux_live_component',
        string $method = 'post',
        int $urlReferenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        ?string $fetchCredentials = null,
    ) {
        parent::__construct($name, $template, $exposePublicProps, $attributesVar);

        $this->defaultAction = $defaultAction;
        $this->route = $route;
        $this->method = strtolower($method);
        $this->urlReferenceType = $urlReferenceType;
        $this->fetchCredentials = $fetchCredentials;

        if (!\in_array($this->method, ['get', 'post'], true)) {
            throw new \UnexpectedValueException('$method must be either \'get\' or \'post\'.');
        }

        if (null !== $fetchCredentials && !\in_array($fetchCredentials, ['same-origin', 'include', 'omit'], true)) {
            throw new \UnexpectedValueException('$fetchCredentials must be either \'same-origin\', \'include\' or \'omit\'.');
        }
    }

    /**
     * @internal
     */
    public function serviceConfig(): array
    {
        return array_merge(parent::serviceConfig(), [
            'default_action' => $this->defaultAction,
            'live' => true,
            'route' => $this->route,
            'method' => $this->method,
            'url_reference_type' => $this->urlReferenceType,
            'fetch_credentials' => $this->fetchCredentials,
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
