<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Provider;

use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Image\Exception\InvalidArgumentException;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
abstract class AbstractProviderFactory
{
    public function supports(Dsn $dsn): bool
    {
        return \in_array($dsn->getScheme(), $this->getSupportedSchemes(), true);
    }

    /**
     * @return list<string>
     */
    abstract protected function getSupportedSchemes(): array;

    /**
     * Declares the DSN query options this factory reads.
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException when the DSN options do not match what {@see configureOptions()} declares
     */
    protected function resolveOptions(Dsn $dsn): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $dsn->getOptions();

        // OptionsResolver would report "Defined options are: """ for a provider that takes none.
        if ([] === $resolver->getDefinedOptions() && [] !== $options) {
            throw new InvalidArgumentException(\sprintf('Invalid "%s" image provider DSN: the provider takes no option, "%s" given.', $dsn->getScheme(), implode('", "', array_keys($options))));
        }

        foreach ($options as $name => $value) {
            if ($resolver->isDefined($name) && !\is_string($value)) {
                throw new InvalidArgumentException(\sprintf('Invalid "%s" image provider DSN: the "%s" option must be a string, "%s" given.', $dsn->getScheme(), $name, get_debug_type($value)));
            }
        }

        try {
            return $resolver->resolve($options);
        } catch (OptionsResolverException $e) {
            throw new InvalidArgumentException(\sprintf('Invalid "%s" image provider DSN: %s', $dsn->getScheme(), $e->getMessage()), 0, $e);
        }
    }
}
