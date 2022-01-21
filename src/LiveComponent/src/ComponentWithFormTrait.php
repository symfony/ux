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

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Attribute\BeforeReRender;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 */
trait ComponentWithFormTrait
{
    private ?FormView $formView = null;
    private ?FormInterface $formInstance = null;

    /**
     * Holds the name prefix the form uses.
     */
    #[LiveProp]
    public ?string $formName = null;

    /**
     * Holds the raw form values.
     */
    #[LiveProp(writable: true, fieldName: 'getFormName()')]
    public ?array $formValues = null;

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
     * Return the full, top-level, Form object that this component uses.
     */
    abstract protected function instantiateForm(): FormInterface;

    /**
     * Override in your class if you need extra mounted values.
     *
     * Call $this->setForm($form) manually in that situation
     * if you're passing in an initial form.
     */
    public function mount(?FormView $form = null)
    {
        if ($form) {
            $this->setForm($form);
        }
    }

    /**
     * Make sure the form has been submitted.
     *
     * This primarily applies to a re-render where $actionName is null.
     * But, in the event that there is an action and the form was
     * not submitted manually, it will be submitted here.
     */
    #[BeforeReRender]
    public function submitFormOnRender(): void
    {
        if (!$this->getFormInstance()->isSubmitted()) {
            $this->submitForm(false);
        }
    }

    /**
     * Returns the FormView object: useful for rendering your form/fields!
     */
    public function getForm(): FormView
    {
        if (null === $this->formView) {
            $this->formView = $this->getFormInstance()->createView();
        }

        return $this->formView;
    }

    /**
     * Call this from mount() if your component receives a FormView.
     *
     * If your are not passing a FormView into your component, you
     * don't need to call this directly: the form will be set for
     * you from your instantiateForm() method.
     */
    public function setForm(FormView $form): void
    {
        $this->formView = $form;
    }

    public function getFormName(): string
    {
        if (!$this->formName) {
            $this->formName = $this->getForm()->vars['name'];
        }

        return $this->formName;
    }

    public function getFormValues(): array
    {
        return $this->formValues = $this->extractFormValues($this->getForm(), $this->formValues ?? []);
    }

    private function submitForm(bool $validateAll = true): void
    {
        $this->getFormInstance()->submit($this->formValues);

        if ($validateAll) {
            // mark the entire component as validated
            $this->isValidated = true;
            // set fields back to empty, as now the *entire* object is validated.
            $this->validatedFields = [];
        } else {
            // we only want to validate fields in validatedFields
            // but really, everything is validated at this point, which
            // means we need to clear validation on non-matching fields
            $this->clearErrorsForNonValidatedFields($this->getFormInstance(), $this->getFormName());
        }

        if (!$this->getFormInstance()->isValid()) {
            throw new UnprocessableEntityHttpException('Form validation failed in component');
        }
    }

    /**
     * Returns a hierarchical array of the entire form's values.
     *
     * This is used to pass the initial values into the live component's
     * frontend, and it's meant to equal the raw POST data that would
     * be sent if the form were submitted without modification.
     */
    private function extractFormValues(FormView $formView, array $values = []): array
    {
        foreach ($formView->children as $child) {
            $name = $child->vars['name'];
            if (!($child->vars['expanded'] ?? false) && \count($child->children) > 0) {
                $values[$name] = $this->extractFormValues($child, $values[$name] ?? []);

                continue;
            }

            if (null !== ($values[$name] ?? null)) {
                continue;
            }

            if (\array_key_exists('checked', $child->vars)) {
                // special handling for check boxes
                $values[$name] = $child->vars['checked'] ? $child->vars['value'] : null;
            } else {
                $values[$name] = $child->vars['value'];
            }
        }

        return $values;
    }

    private function getFormInstance(): FormInterface
    {
        if (null === $this->formInstance) {
            $this->formInstance = $this->instantiateForm();
        }

        return $this->formInstance;
    }

    private function clearErrorsForNonValidatedFields(Form $form, $currentPath = ''): void
    {
        if (!$currentPath || !\in_array($currentPath, $this->validatedFields, true)) {
            $form->clearErrors();
        }

        foreach ($form as $name => $child) {
            $this->clearErrorsForNonValidatedFields($child, sprintf('%s.%s', $currentPath, $name));
        }
    }
}
