<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComputedPropertiesProxy
{
    private array $cache = [];

    /**
     * @internal
     */
    public function __construct(private object $component)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        if ($arguments) {
            throw new \InvalidArgumentException('Passing arguments to computed methods is not supported.');
        }

        if (isset($this->component->$name)) {
            // try property
            return $this->component->$name;
        }

        if ($this->component instanceof \ArrayAccess && isset($this->component[$name])) {
            return $this->component[$name];
        }

        $method = $this->normalizeMethod($name);

        if (isset($this->cache[$method])) {
            return $this->cache[$method];
        }

        if ((new \ReflectionMethod($this->component, $method))->getNumberOfRequiredParameters()) {
            throw new \LogicException('Cannot use computed methods for methods with required parameters.');
        }

        return $this->cache[$method] = $this->component->$method();
    }

    private function normalizeMethod(string $name): string
    {
        if (method_exists($this->component, $name)) {
            return $name;
        }

        foreach (['get', 'is', 'has'] as $prefix) {
            if (method_exists($this->component, $method = \sprintf('%s%s', $prefix, ucfirst($name)))) {
                return $method;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Component "%s" does not have a "%s" method.', $this->component::class, $name));
    }
}
