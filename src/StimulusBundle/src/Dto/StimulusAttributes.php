<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Dto;

use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Runtime\EscaperRuntime;

/**
 * Helper to build Stimulus-related HTML attributes.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class StimulusAttributes implements \Stringable, \IteratorAggregate
{
    private array $attributes = [];

    private array $controllers = [];
    private array $actions = [];
    private array $targets = [];

    public function __construct(private Environment $env)
    {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->toArray());
    }

    public function addController(string $controllerName, array $controllerValues = [], array $controllerClasses = [], array $controllerOutlets = []): void
    {
        $controllerName = $this->normalizeControllerName($controllerName);
        $this->controllers[] = $controllerName;

        foreach ($controllerValues as $key => $value) {
            if (null === $value) {
                continue;
            }

            $key = $this->normalizeKeyName($key);
            $value = $this->getFormattedValue($value);

            $this->attributes['data-'.$controllerName.'-'.$key.'-value'] = $value;
        }

        foreach ($controllerClasses as $key => $class) {
            $key = $this->normalizeKeyName($key);

            $this->attributes['data-'.$controllerName.'-'.$key.'-class'] = $class;
        }

        foreach ($controllerOutlets as $outlet => $selector) {
            $outlet = $this->normalizeControllerName($outlet);

            $this->attributes['data-'.$controllerName.'-'.$outlet.'-outlet'] = $selector;
        }
    }

    /**
     * @param array $parameters Parameters to pass to the action. Optional.
     */
    public function addAction(string $controllerName, string $actionName, ?string $eventName = null, array $parameters = []): void
    {
        $controllerName = $this->normalizeControllerName($controllerName);
        $this->actions[] = [
            'controllerName' => $controllerName,
            'actionName' => $actionName,
            'eventName' => $eventName,
        ];

        foreach ($parameters as $name => $value) {
            $key = $this->normalizeKeyName($name);

            $this->attributes['data-'.$controllerName.'-'.$key.'-param'] = $this->getFormattedValue($value);
        }
    }

    /**
     * @param string      $controllerName the Stimulus controller name
     * @param string|null $targetNames    The space-separated list of target names if a string is passed to the 1st argument. Optional.
     */
    public function addTarget(string $controllerName, ?string $targetNames = null): void
    {
        if (null === $targetNames) {
            return;
        }

        $controllerName = $this->normalizeControllerName($controllerName);

        $this->targets['data-'.$controllerName.'-target'] = $targetNames;
    }

    public function addAttribute(string $name, string $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __toString(): string
    {
        $attributes = [];

        if ($this->controllers) {
            $attributes[] = 'data-controller="'.$this->escape(implode(' ', $this->controllers)).'"';
        }

        if ($this->actions) {
            $actions = [];
            foreach ($this->actions as ['controllerName' => $controllerName, 'actionName' => $actionName, 'eventName' => $eventName]) {
                $action = $this->escape($controllerName.'#'.$actionName);
                if (null !== $eventName) {
                    // done separately so we can escape, but avoid escaping ->
                    $action = $this->escape($eventName).'->'.$action;
                }

                $actions[] = $action;
            }

            $attributes[] = 'data-action="'.implode(' ', $actions).'"';
        }

        foreach ($this->targets as $k => $v) {
            $attributes[] = $this->escape($k, 'html_attr').'="'.$this->escape($v).'"';
        }

        foreach ($this->attributes as $k => $v) {
            $attributes[] = $this->escape($k, 'html_attr').'="'.$this->escape($v).'"';
        }

        return implode(' ', $attributes);
    }

    public function toArray(): array
    {
        $actions = array_map(function (array $actionData): string {
            $controllerName = $actionData['controllerName'];
            $actionName = $actionData['actionName'];
            $eventName = $actionData['eventName'];

            $action = $controllerName.'#'.$actionName;
            if (null !== $eventName) {
                $action = $eventName.'->'.$action;
            }

            return $action;
        }, $this->actions);

        $attributes = [];

        if ($this->controllers) {
            $attributes['data-controller'] = implode(' ', $this->controllers);
        }

        if ($actions) {
            $attributes['data-action'] = implode(' ', $actions);
        }

        if ($this->targets) {
            $attributes = array_merge($attributes, $this->targets);
        }

        return array_merge($attributes, $this->attributes);
    }

    public function toEscapedArray(): array
    {
        $escaped = [];
        foreach ($this->toArray() as $key => $value) {
            $escaped[$key] = $this->escape($value);
        }

        return $escaped;
    }

    private function getFormattedValue(mixed $value): string
    {
        if ($value instanceof \Stringable || (\is_object($value) && \is_callable([$value, '__toString']))) {
            $value = (string) $value;
        } elseif (!\is_scalar($value)) {
            $value = json_encode($value);
        } elseif (\is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    private function escape(mixed $value, string $strategy = 'html'): string
    {
        if (class_exists(EscaperRuntime::class)) {
            return $this->env->getRuntime(EscaperRuntime::class)->escape($value, $strategy);
        }

        if (method_exists(EscaperExtension::class, 'escape')) {
            return EscaperExtension::escape($this->env, $value, $strategy);
        }

        // since twig/twig 3.9.0: Using the internal "twig_escape_filter" function is deprecated.
        return (string) twig_escape_filter($this->env, $value, $strategy);
    }

    /**
     * Normalize a Stimulus controller name into its HTML equivalent (no special character and / becomes --).
     *
     * @see https://stimulus.hotwired.dev/reference/controllers
     */
    private function normalizeControllerName(string $controllerName): string
    {
        return preg_replace('/^@/', '', str_replace('_', '-', str_replace('/', '--', $controllerName)));
    }

    /**
     * Normalize a Stimulus Value API key into its HTML equivalent ("kebab case").
     * Backport features from symfony/string.
     *
     * @see https://stimulus.hotwired.dev/reference/values
     */
    private function normalizeKeyName(string $str): string
    {
        // Adapted from ByteString::camel
        $str = ucfirst(str_replace(' ', '', ucwords(preg_replace('/[^a-zA-Z0-9\x7f-\xff]++/', ' ', $str))));

        // Adapted from ByteString::snake
        return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], '\1-\2', $str));
    }
}
