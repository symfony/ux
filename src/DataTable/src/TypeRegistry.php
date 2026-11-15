<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Psr\Container\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\DataTable\Exception\InvalidArgumentException;

/**
 * Resolves a type, its parents and its options.
 *
 * Types registered as services are fetched from the locator, the others are
 * instantiated without arguments.
 *
 * @internal
 *
 * @author Simon Daigre <simon@daig.re>
 */
final class TypeRegistry
{
    /**
     * @var array<class-string, TypeInterface>
     */
    private array $types = [];

    public function __construct(
        private readonly ?ContainerInterface $locator = null,
    ) {
    }

    /**
     * @template T of TypeInterface
     *
     * @param class-string<T>      $interface
     * @param array<string, mixed> $options
     *
     * @return array{non-empty-list<T>, array<string, mixed>} the types from the root to the given one, and the resolved options
     */
    public function resolve(string $class, string $interface, array $options = []): array
    {
        $types = [];
        $resolver = new OptionsResolver();

        do {
            if (!is_a($class, $interface, true)) {
                throw new InvalidArgumentException(\sprintf('The type "%s" must implement "%s".', $class, $interface));
            }

            /** @var T $type */
            $type = $this->types[$class] ?? ($this->locator?->has($class) ? $this->locator->get($class) : new $class());
            $this->types[$class] = $type;
            array_unshift($types, $type);
        } while (null !== $class = $type->getParent());

        foreach ($types as $type) {
            $type->configureOptions($resolver);
        }

        /** @var array<string, mixed> $resolved the options keep their names */
        $resolved = $resolver->resolve($options);

        return [$types, $resolved];
    }
}
