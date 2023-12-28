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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Jean-Paul van der Wegen <info@jpvdw.nl>
 *
 * @experimental
 *
 * @internal
 */
class DoctrineArrayCollectionHydrationExtension implements HydrationExtensionInterface
{
    use DoctrineHydrationTrait;

    public function __construct(protected readonly iterable $managerRegistries)
    {
    }

    public function supports(string $className): bool
    {
        return ArrayCollection::class === $className;
    }

    public function hydrate(mixed $value, string $className): ?object
    {
        if (!\is_array($value)) {
            throw new \InvalidArgumentException(sprintf('Cannot hydrate ArrayCollection. Value must be an array, %s given.', \gettype($value)));
        }

        $output = new ArrayCollection();
        foreach ($value as $item) {
            $this->validateHydrateItem($item);

            $output->add($this->getObject($item['className'], $item['identifierValue']));
        }

        return $output;
    }

    private function validateHydrateItem(array $item): void
    {
        $requiredKeys = ['className', 'identifierValue'];

        foreach ($requiredKeys as $key) {
            if (!isset($item[$key])) {
                throw new \InvalidArgumentException(sprintf('Cannot hydrate ArrayCollection: key "%s" is missing', $key));
            }
        }
    }

    public function dehydrate(object $object): mixed
    {
        $output = [];
        foreach ($object as $entityObject) {
            $identifierValue = $this->getIdentifierValue($entityObject);

            if (empty($identifierValue)) {
                throw new \InvalidArgumentException(sprintf('Cannot dehydrate ArrayCollection that contains a non-persisted entity "%s".', $entityObject::class));
            }

            $output[] = [
                'className' => $entityObject::class,
                'identifierValue' => $identifierValue,
            ];
        }

        return $output;
    }
}
