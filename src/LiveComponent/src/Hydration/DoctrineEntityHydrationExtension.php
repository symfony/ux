<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Hydration;

class DoctrineEntityHydrationExtension extends AbstractDoctrineHydrationExtension implements HydrationExtensionInterface
{
    public function supports(string $className): bool
    {
        return null !== $this->objectManagerFor($className);
    }

    public function hydrate(mixed $value, string $className): ?object
    {
        // an empty array means a non-persisted entity
        // we support instantiating with no constructor args
        if (\is_array($value) && 0 === \count($value)) {
            return new $className();
        }

        // e.g. an empty string
        if (!$value) {
            return null;
        }

        // $data is the single identifier or array of identifiers
        if (\is_scalar($value) || (\is_array($value) && isset($value[0]))) {
            return $this->objectManagerFor($className)->find($className, $value);
        }

        throw new \InvalidArgumentException(sprintf('Cannot hydrate Doctrine entity "%s". Value of type "%s" is not supported.', $className, get_debug_type($value)));
    }

    public function dehydrate(object $object): mixed
    {
        return $this->getIdentifierValue($object);
    }
}
