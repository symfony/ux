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

use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\Util\LiveFormUtility;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
trait ComponentWithFormTrait
{
    #[ExposeInTemplate(name: 'form', getter: 'getFormView')]
    private ?FormView $formView = null;
    private ?FormInterface $form = null;

    /**
     * Holds the name prefix the form uses.
     */
    #[LiveProp]
    public ?string $formName = null;

    /**
     * Holds the raw form values.
     */
    #[LiveProp(writable: true, fieldName: 'getFormName()')]
    public array $formValues = [];

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

    private bool $shouldAutoSubmitForm = true;

    /**
     * Return the full, top-level, Form object that this component uses.
     */
    abstract protected function instantiateForm(): FormInterface;

    /**
     * @internal
     */
    #[PostMount]
    public function initializeForm(array $data): array
    {
        // allow the FormView object to be passed into the component() as "form"
        if (\array_key_exists('form', $data)) {
            $this->formView = $data['form'];
            $this->useNameAttributesAsModelName();

            unset($data['form']);

            // if a FormView is passed in and it contains any errors, then
            // we mark that this entire component has been validated so that
            // all validation errors continue showing on re-render
            if ($this->formView && LiveFormUtility::doesFormContainAnyErrors($this->formView)) {
                $this->isValidated = true;
                $this->validatedFields = [];
            }
        }

        // set the formValues from the initial form view's data
        $this->formValues = $this->extractFormValues($this->getFormView());

        return $data;
    }

    /**
     * Make sure the form has been submitted.
     *
     * This primarily applies to a re-render where $actionName is null.
     * But, in the event that there is an action and the form was
     * not submitted manually, it will be submitted here.
     *
     * @internal
     */
    #[PreReRender]
    public function submitFormOnRender(): void
    {
        if ($this->shouldAutoSubmitForm) {
            $this->submitForm($this->isValidated);
        }
    }

    public function getFormView(): FormView
    {
        if (null === $this->formView) {
            $this->formView = $this->getForm()->createView();
            $this->useNameAttributesAsModelName();
        }

        return $this->formView;
    }

    public function getFormName(): string
    {
        if (null === $this->formName) {
            $this->formName = $this->getFormView()->vars['name'];
        }

        return $this->formName;
    }

    /**
     * Reset the form to its initial state, so it can be used again.
     */
    private function resetForm(): void
    {
        // prevent the system from trying to submit this reset form
        $this->shouldAutoSubmitForm = false;
        $this->form = null;
        $this->formView = null;
        $this->formValues = $this->extractFormValues($this->getFormView());
    }

    private function submitForm(bool $validateAll = true): void
    {
        if (null !== $this->formView) {
            throw new \LogicException('The submitForm() method is being called, but the FormView has already been built. Are you calling $this->getForm() - which creates the FormView - before submitting the form?');
        }

        $form = $this->getForm();
        $form->submit($this->formValues);
        $this->shouldAutoSubmitForm = false;

        if ($validateAll) {
            // mark the entire component as validated
            $this->isValidated = true;
            // set fields back to empty, as now the *entire* object is validated.
            $this->validatedFields = [];
        } else {
            // we only want to validate fields in validatedFields
            // but really, everything is validated at this point, which
            // means we need to clear validation on non-matching fields
            $this->clearErrorsForNonValidatedFields($form, $form->getName());
        }

        // re-extract the "view" values in case the submitted data
        // changed the underlying data or structure of the form
        $this->formValues = $this->extractFormValues($this->getFormView());

        // remove any validatedFields that do not exist in data anymore
        $this->validatedFields = LiveFormUtility::removePathsNotInData(
            $this->validatedFields ?? [],
            [$form->getName() => $this->formValues],
        );

        if (!$form->isValid()) {
            throw new UnprocessableEntityHttpException('Form validation failed in component.');
        }
    }

    private function getForm(): FormInterface
    {
        if (null === $this->form) {
            $this->form = $this->instantiateForm();
        }

        return $this->form;
    }

    /**
     * Automatically adds data-model="*" to the form element.
     *
     * This makes it so that all fields will automatically become
     * "models", using their "name" attribute.
     *
     * This is for convenience: it prevents you from needing to
     * manually add data-model="" to every field. Effectively,
     * having name="foo" becomes the equivalent to data-model="foo".
     *
     * To disable or change this behavior, override the
     * the getDataModelValue() method.
     */
    private function useNameAttributesAsModelName(): void
    {
        $modelValue = $this->getDataModelValue();
        $attributes = $this->getFormView()->vars['attr'] ?: [];
        if (null === $modelValue) {
            unset($attributes['data-model']);
        } else {
            $attributes['data-model'] = $modelValue;
        }

        $this->getFormView()->vars['attr'] = $attributes;
    }

    /**
     * Controls the data-model="" value that will be rendered on the <form> tag.
     *
     * This default value will cause the component to re-render each time
     * a field "changes". Override this in your controller to change the behavior.
     */
    private function getDataModelValue(): ?string
    {
        return 'on(change)|*';
    }

    /**
     * Returns a hierarchical array of the entire form's values.
     *
     * This is used to pass the initial values into the live component's
     * frontend, and it's meant to equal the raw POST data that would
     * be sent if the form were submitted without modification.
     */
    private function extractFormValues(FormView $formView): array
    {
        $values = [];

        foreach ($formView->children as $child) {
            $name = $child->vars['name'];

            // if there are children, expand their values recursively
            // UNLESS the field is "expanded": in that case the value
            // is already correct. For example, an expanded ChoiceType with
            // options "text" and "phone" would already have a value in the format
            // ["text"] (assuming "text" is checked and "phone" is not).
            // "compound" is how we know if a field holds children. The extra
            // "compound_data" is a special flag to workaround the fact that
            // the "autocomplete" library fakes their compound fake incorrectly.
            $isCompound = $child->vars['compound_data'] ?? $child->vars['compound'] ?? false;
            if ($isCompound && !($child->vars['expanded'] ?? false)) {
                $values[$name] = $this->extractFormValues($child);

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

    private function clearErrorsForNonValidatedFields(FormInterface $form, string $currentPath = ''): void
    {
        if ($form instanceof ClearableErrorsInterface && (!$currentPath || !\in_array($currentPath, $this->validatedFields, true))) {
            $form->clearErrors();
        }

        foreach ($form as $name => $child) {
            $this->clearErrorsForNonValidatedFields($child, \sprintf('%s.%s', $currentPath, $name));
        }
    }
}
