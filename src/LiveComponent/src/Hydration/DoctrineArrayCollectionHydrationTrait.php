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
class DoctrineArrayCollectionHydrationTrait implements HydrationExtensionInterface
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
        $output = new ArrayCollection();
        foreach ($value as $item) {
            $output->add($this->getObject($item['class'], $item['identifierValue']));
        }

        return $output;
    }

    public function dehydrate(object $object): mixed
    {
        $output = [];
        foreach ($object as $entityObject) {
            $identifierValue = $this->getIdentifierValue($entityObject);

            if (empty($identifierValue)) {
                throw new \InvalidArgumentException(sprintf('Cannot hydrate ArrayCollection that contains a non-persisted entity "%s".', $entityObject::class));
            }

            $output[] = [
                'class' => $entityObject::class,
                'identifierValue' => $identifierValue,
            ];
        }

        return $output;
    }
}
