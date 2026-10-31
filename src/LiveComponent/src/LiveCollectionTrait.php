<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 */
trait LiveCollectionTrait
{
    use ComponentWithFormTrait;

    /**
     * Tracks indexes removed from each collection so that newly added items
     * never reuse a previously used index, which would make the form re-bind
     * to a stale entity from the initial collection.
     *
     * Keyed by the collection field name passed to the add/remove actions.
     *
     * @var array<string, list<int>>
     */
    #[LiveProp]
    public array $liveCollectionRemovedKeys = [];

    #[LiveAction]
    public function addCollectionItem(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name): void
    {
        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);

        if (!\is_array($data)) {
            $propertyAccessor->setValue($this->formValues, $propertyPath, []);
            $data = [];
        }

        $usedKeys = array_merge(array_keys($data), $this->liveCollectionRemovedKeys[$name] ?? []);
        $index = [] !== $usedKeys ? max($usedKeys) + 1 : 0;
        $propertyAccessor->setValue($this->formValues, $propertyPath."[$index]", []);
    }

    #[LiveAction]
    public function removeCollectionItem(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name, #[LiveArg] int $index): void
    {
        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);
        unset($data[$index]);
        $propertyAccessor->setValue($this->formValues, $propertyPath, $data);

        $this->liveCollectionRemovedKeys[$name][] = $index;
    }

    private function fieldNameToPropertyPath(string $collectionFieldName, string $rootFormName): string
    {
        $propertyPath = $collectionFieldName;

        if (str_starts_with($collectionFieldName, $rootFormName)) {
            $propertyPath = substr_replace($collectionFieldName, '', 0, mb_strlen($rootFormName));
        }

        if (!str_starts_with($propertyPath, '[')) {
            $propertyPath = "[$propertyPath]";
        }

        return $propertyPath;
    }
}
