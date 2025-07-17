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

use Symfony\Component\Validator\ConstraintViolation;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
interface ComponentValidatorInterface
{
    /**
     * Returns an array - keyed by the property path - containing
     * another array of validation errors.
     *
     * For example:
     *
     *      [
     *          'firstName' => [ConstraintViolation, ConstraintViolation],
     *      ]
     *
     * @return ConstraintViolation[][]
     */
    public function validate(object $component): array;

    /**
     * Returns an array of violations for this one specific property.
     *
     * @return ConstraintViolation[]
     */
    public function validateField(object $component, string $propertyName): array;
}
