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
 *
 * @experimental
 */
trait LiveCollectionTrait
{
    use ComponentWithFormTrait;

    #[LiveAction]
    public function addCollectionItem(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name): void
    {
        if (str_starts_with($name, $this->formName)) {
            $name = substr_replace($name, '', 0, mb_strlen($this->formName));
        }

        $data = $propertyAccessor->getValue($this->formValues, $name);

        if (!\is_array($data)) {
            $propertyAccessor->setValue($this->formValues, $name, []);
            $data = [];
        }

        $index = [] !== $data ? max(array_keys($data)) + 1 : 0;
        $propertyAccessor->setValue($this->formValues, $name."[$index]", []);
    }

    #[LiveAction]
    public function removeCollectionItem(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name, #[LiveArg] int $index): void
    {
        if (str_starts_with($name, $this->formName)) {
            $name = substr_replace($name, '', 0, mb_strlen($this->formName));
        }

        $data = $propertyAccessor->getValue($this->formValues, $name);
        unset($data[$index]);
        $propertyAccessor->setValue($this->formValues, $name, $data);
    }
}
