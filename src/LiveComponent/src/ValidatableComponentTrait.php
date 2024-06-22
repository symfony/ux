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

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Component\ComponentValidationErrors;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
trait ValidatableComponentTrait
{
    private ?ComponentValidatorInterface $componentValidator = null;
    private ?ComponentValidationErrors $validationErrors = null;

    /**
     * Tracks whether this entire component has been validated.
     *
     * This is used to know if validation should be automatically applied
     * when rendering.
     */
    #[LiveProp(writable: true)]
    public bool $isValidated = false;

    /**
     * Tracks which specific fields have been validated.
     *
     * Instead of validating the entire object (isValidated),
     * the component can be validated, field-by-field.
     */
    #[LiveProp(writable: true)]
    public array $validatedFields = [];

    /**
     * Validate the entire component.
     *
     * This stores the validation errors: accessible via the getError() method.
     */
    public function validate(bool $throw = true): void
    {
        // set fields back to empty, as now the *entire* object is validated.
        $this->validatedFields = [];
        $this->isValidated = true;
        $this->getValidationErrors()->setAll($this->getValidator()->validate($this));

        if ($throw && \count($this->getValidationErrors()) > 0) {
            throw new UnprocessableEntityHttpException('Component validation failed');
        }
    }

    /**
     * Validates a single property (or property path) only.
     *
     * If a property path - like post.title - is passed, this will
     * validate the *entire* "post" property. It will then loop
     * over all the errors and collect only those for "post.title".
     */
    public function validateField(string $propertyName, bool $throw = true): void
    {
        if (!\in_array($propertyName, $this->validatedFields, true)) {
            $this->validatedFields[] = $propertyName;
        }

        $errors = $this->getValidator()->validateField($this, $propertyName);
        $this->getValidationErrors()->set($propertyName, $errors);

        if ($throw && \count($errors) > 0) {
            throw new UnprocessableEntityHttpException(\sprintf('The "%s" field of the component failed validation.', $propertyName));
        }
    }

    /**
     * Return the first validation error - if any - for a specific field.
     */
    public function getError(string $propertyPath): ?ConstraintViolation
    {
        $violations = $this->getValidationErrors()->getViolations($propertyPath);

        return $violations[0] ?? null;
    }

    #[ExposeInTemplate('_errors')]
    public function getErrorsObject(): ComponentValidationErrors
    {
        return $this->getValidationErrors();
    }

    /**
     * @return ConstraintViolation[]
     */
    public function getErrors(string $propertyPath): array
    {
        return $this->getValidationErrors()->getAll($propertyPath);
    }

    public function isValid(): bool
    {
        return 0 === \count($this->getValidationErrors());
    }

    /**
     * Completely reset validation on this component.
     */
    public function clearValidation(): void
    {
        $this->isValidated = false;
        $this->validatedFields = [];
        $this->getValidationErrors()->clear();
    }

    /**
     * @internal
     */
    #[PostHydrate]
    public function validateAfterHydration(): void
    {
        if ($this->isValidated) {
            $this->validate(false);

            return;
        }

        if (\count($this->validatedFields) > 0) {
            foreach ($this->validatedFields as $validatedField) {
                $this->validateField($validatedField, false);
            }
        }
    }

    /**
     * @internal
     */
    #[Required]
    public function setComponentValidator(ComponentValidatorInterface $componentValidator): void
    {
        $this->componentValidator = $componentValidator;
    }

    private function resetValidation(): void
    {
        $this->isValidated = false;
        $this->validatedFields = [];
        $this->validationErrors = null;
    }

    private function getValidator(): ComponentValidatorInterface
    {
        if (!$this->componentValidator) {
            throw new \InvalidArgumentException(\sprintf('The ComponentValidator service was not injected into %s. Did you forget to autowire this service or configure the setComponentValidator() call?', static::class));
        }

        return $this->componentValidator;
    }

    private function getValidationErrors(): ComponentValidationErrors
    {
        if (null === $this->validationErrors) {
            $this->validationErrors = new ComponentValidationErrors();
        }

        return $this->validationErrors;
    }
}
