<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Builder;

use Symfony\UX\StimulusBundle\Helper\StimulusSyntaxHelper;

class StimulusAttributeBuilder
{
    private string $controllerName;
    private array $actions = [];
    private array $values = [];
    private array $classes = [];
    private array $targets = [];
    private array $outlets = [];
    private array $attributes = [];

    public static function controller(string $controllerName): self
    {
        $builder = new self();
        $builder->controllerName = $controllerName;

        return $builder;
    }

    public function action(string $actionName, ?string $eventName = null, array $parameters = []): self
    {
        $this->actions[] = [
            'actionName' => $actionName,
            'eventName' => $eventName,
            'parameters' => $parameters,
        ];

        return $this;
    }

    public function value(string $key, mixed $value): self
    {
        $this->values[$key] = $value;

        return $this;
    }

    public function class(string $key, mixed $value): self
    {
        $this->classes[$key] = $value;

        return $this;
    }

    public function target(string $key, mixed $value): self
    {
        $this->targets[$key] = $value;

        return $this;
    }

    public function outlet(string $identifier, string $outlet, mixed $selector): self
    {
        $this->outlets[] = [
            'identifier' => $identifier,
            'outlet' => $outlet,
            'selector' => $selector,
        ];

        return $this;
    }

    public function build(): array
    {
        $syntaxHelper = new StimulusSyntaxHelper();
        $controllerName = $syntaxHelper->normalizeControllerName($this->controllerName);

        $this->attributes['data-controller'] = $controllerName;

        // Actions
        $this->attributes = array_merge(
            $this->attributes,
            ...array_map(function (array $actionData) use ($controllerName): array {
                $actionName = htmlspecialchars($actionData['actionName']);
                $eventName = $actionData['eventName'];

                $action = \sprintf('%s#%s', $controllerName, $actionName);
                if (null !== $eventName) {
                    $action = \sprintf('%s->%s', htmlspecialchars($eventName), $action);
                }

                return ['data-action' => $action];
            }, $this->actions)
        );

        // Action Parameters
        $this->attributes = array_merge(
            $this->attributes,
            ...array_map(
                function (array $actionData) use ($controllerName, $syntaxHelper): array {
                    $parameters = [];

                    foreach ($actionData['parameters'] as $name => $value) {
                        $key = $syntaxHelper->normalizeKeyName($name);
                        $value = $syntaxHelper->getFormattedValue($value);

                        $parameters[\sprintf(
                            'data-%s-%s-param',
                            $controllerName,
                            $key
                        )] = htmlspecialchars($value);
                    }

                    return $parameters;
                },
                $this->actions
            )
        );

        // Values
        foreach ($this->values as $key => $value) {
            if (null === $value) {
                continue;
            }

            $key = $syntaxHelper->normalizeKeyName($key);
            $value = $syntaxHelper->getFormattedValue($value);

            $this->attributes[\sprintf('data-%s-%s-value', $controllerName, $key)] = $value;
        }

        // Classes
        foreach ($this->classes as $key => $class) {
            $key = $syntaxHelper->normalizeKeyName($key);

            $this->attributes[\sprintf('data-%s-%s-class', $controllerName, $key)] = $class;
        }

        // Outlets
        foreach ($this->outlets as $outletItem) {
            $identifier = $syntaxHelper->normalizeControllerName($outletItem['identifier']);
            $outlet = $syntaxHelper->normalizeKeyName($outletItem['outlet']);

            $this->attributes[\sprintf('data-%s-%s-outlet', $identifier, $outlet)] = htmlspecialchars(
                $outletItem['selector']
            );
        }

        // Targets
        foreach ($this->targets as $key => $target) {
            $key = $syntaxHelper->normalizeKeyName($key);

            $this->attributes[\sprintf('data-%s-target', $key)] = htmlspecialchars($target);
        }

        return $this->attributes;
    }
}
