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

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\UX\TwigComponent\Attribute\ComponentCache;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComputedPropertiesProxy
{
    private array $cache = [];

    /**
     * @internal
     */
    public function __construct(
        private object $component,
        private ?ContainerInterface $container = null,
    ) {
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

        $reflectionMethod = new \ReflectionMethod($this->component, $method);

        if ($reflectionMethod->getNumberOfRequiredParameters()) {
            throw new \LogicException('Cannot use computed methods for methods with required parameters.');
        }

        if ($attributes = $reflectionMethod->getAttributes(ComponentCache::class)) {
            $attribute = $attributes[0]->newInstance();
            $poolName = $attribute->pool ?? 'cache.app';

            if ($this->container && $this->container->has($poolName)) {
                $pool = $this->container->get($poolName);

                if ($pool instanceof CacheItemPoolInterface) {
                    // Generate an automatic key if none is provided
                    $key = $attribute->key ?? \sprintf('%s_%s_%s', str_replace('\\', '_', $this->component::class), $method, md5(serialize(get_object_vars($this->component))));

                    $item = $pool->getItem($key);
                    if ($item->isHit()) {
                        return $this->cache[$method] = $item->get();
                    }

                    $value = $this->component->$method();
                    $item->set($value);

                    if (null !== $attribute->expiresAfter) {
                        $item->expiresAfter($attribute->expiresAfter instanceof \DateInterval ? $attribute->expiresAfter : (int) $attribute->expiresAfter);
                    }

                    if ($attribute->tags && method_exists($item, 'tag')) {
                        $item->tag($attribute->tags);
                    }

                    $pool->save($item);

                    return $this->cache[$method] = $value;
                }
            }
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
