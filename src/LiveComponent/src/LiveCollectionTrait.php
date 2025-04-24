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

/**
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 */
trait LiveCollectionTrait
{
    use ComponentWithFormTrait;

    #[LiveAction]
    public function addCollectionItem(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name): void
    {
        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);

        if (!\is_array($data)) {
            $propertyAccessor->setValue($this->formValues, $propertyPath, []);
            $data = [];
        }

        $index = [] !== $data ? max(array_keys($data)) + 1 : 0;
        $propertyAccessor->setValue($this->formValues, $propertyPath."[$index]", []);
    }

    #[LiveAction]
    public function removeCollectionItem(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name, #[LiveArg] int $index): void
    {
        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);
        unset($data[$index]);
        $propertyAccessor->setValue($this->formValues, $propertyPath, $data);
    }

    #[LiveAction]
    public function moveCollectionItemUp(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name, #[LiveArg] int $index): void
    {
        if ($index <= 0) {
            return;
        }

        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);

        if (!\is_array($data) || !isset($data[$index]) || !isset($data[$index - 1])) {
            return;
        }

        $formConfig = $this->getForm()->get($name)->getConfig();
        $orderPropertyPath = $formConfig->getOption('order_property_path');

        if (null !== $orderPropertyPath) {
            // Swap positions using the specified order property
            $prevItemOrder = $propertyAccessor->getValue($data[$index - 1], $orderPropertyPath);
            $currentItemOrder = $propertyAccessor->getValue($data[$index], $orderPropertyPath);

            $propertyAccessor->setValue($data[$index], $orderPropertyPath, $prevItemOrder);
            $propertyAccessor->setValue($data[$index - 1], $orderPropertyPath, $currentItemOrder);
        } else {
            // Legacy behavior - simple array swap (with warning about limitations)
            $temp = $data[$index - 1];
            $data[$index - 1] = $data[$index];
            $data[$index] = $temp;
        }

        $propertyAccessor->setValue($this->formValues, $propertyPath, $data);
    }

    #[LiveAction]
    public function moveCollectionItemDown(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name, #[LiveArg] int $index): void
    {
        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);

        if (!\is_array($data) || !isset($data[$index]) || !isset($data[$index + 1])) {
            return;
        }

        $formConfig = $this->getForm()->get($name)->getConfig();
        $orderPropertyPath = $formConfig->getOption('order_property_path');

        if (null !== $orderPropertyPath) {
            // Swap positions using the specified order property
            $nextItemOrder = $propertyAccessor->getValue($data[$index + 1], $orderPropertyPath);
            $currentItemOrder = $propertyAccessor->getValue($data[$index], $orderPropertyPath);

            $propertyAccessor->setValue($data[$index], $orderPropertyPath, $nextItemOrder);
            $propertyAccessor->setValue($data[$index + 1], $orderPropertyPath, $currentItemOrder);
        } else {
            // Legacy behavior - simple array swap (with warning about limitations)
            $temp = $data[$index + 1];
            $data[$index + 1] = $data[$index];
            $data[$index] = $temp;
        }

        $propertyAccessor->setValue($this->formValues, $propertyPath, $data);
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
