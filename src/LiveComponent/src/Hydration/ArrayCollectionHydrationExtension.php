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
class ArrayCollectionHydrationExtension extends AbstractDoctrineHydrationExtension implements HydrationExtensionInterface
{
    public function supports(string $className): bool
    {
        return ArrayCollection::class === $className;
    }

    public function hydrate(mixed $value, string $className): ?object
    {
        $output = new ArrayCollection();
        foreach ($value as $item) {
            $object = $this->findObject($item['class'], $item['identifierValue']);

            if ($object) {
                $output->add($object);
            }
        }

        return $output;
    }

    public function dehydrate(object $object): mixed
    {
        $output = [];
        foreach ($object as $class) {
            $output[] = [
                'class' => $class::class,
                'identifierValue' => $this->getIdentifierValue($class),
            ];
        }

        return $output;
    }
}
