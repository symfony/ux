import ValueStore from './Component/ValueStore';
import { Directive, parseDirectives } from './Directive/directives_parser';
import { normalizeModelName } from './string_utils';
import Component from './Component';

/**
 * Return the "value" of any given element.
 *
 * This takes into account that the element may be a "multiple"
 * value input, like an <input type="checkbox"> where there are multiple
 * elements. In those cases, it will return the "full", final value
 * for the model, which includes previously-selected values.
 */
export function getValueFromElement(element: HTMLElement, valueStore: ValueStore): string | string[] | null {
    if (element instanceof HTMLInputElement) {
        if (element.type === 'checkbox') {
            const modelNameData = getModelDirectiveFromElement(element);
            if (modelNameData === null) {
                return null;
            }

            const modelValue = valueStore.get(modelNameData.action);
            if (Array.isArray(modelValue)) {
                return getMultipleCheckboxValue(element, modelValue);
            }

            return element.checked ? inputValue(element) : null;
        }

        return inputValue(element);
    }

    if (element instanceof HTMLSelectElement) {
        if (element.multiple) {
            // Select elements with `multiple` option require mapping chosen options to their values
            return Array.from(element.selectedOptions).map((el) => el.value);
        }

        return element.value;
    }

    // element is some other element
    if (element.dataset.value) {
        return element.dataset.value;
    }

    // e.g. a textarea
    if ('value' in element) {
        // the "as" is a cheap way to hint to TypeScript that the value property exists
        return (element as HTMLInputElement).value;
    }

    if (element.hasAttribute('value')) {
        return element.getAttribute('value');
    }

    return null;
}

/**
 * Adapted from https://github.com/livewire/livewire
 */
export function setValueOnElement(element: HTMLElement, value: any): void {
    if (element instanceof HTMLInputElement) {
        if (element.type === 'file') {
            return;
        }

        if (element.type === 'radio') {
            element.checked = element.value == value;

            return;
        }

        if (element.type === 'checkbox') {
            if (Array.isArray(value)) {
                // I'm purposely not using Array.includes here because it's
                // strict, and because of Numeric/String mis-casting, I
                // want the "includes" to be "fuzzy".
                let valueFound = false;
                value.forEach((val) => {
                    if (val == element.value) {
                        valueFound = true;
                    }
                });

                element.checked = valueFound;
            } else {
                element.checked = element.value == value;
            }

            return;
        }
    }

    if (element instanceof HTMLSelectElement) {
        const arrayWrappedValue = [].concat(value).map((value) => {
            return value + '';
        });

        Array.from(element.options).forEach((option) => {
            option.selected = arrayWrappedValue.includes(option.value);
        });

        return;
    }

    value = value === undefined ? '' : value;

    // silencing the typescript warning
    (element as HTMLInputElement).value = value;
}

/**
 * Fetches *all* "data-model" directives for a given element.
 *
 * @param element
 */
export function getAllModelDirectiveFromElements(element: HTMLElement): Directive[] {
    if (!element.dataset.model) {
        return [];
    }

    const directives = parseDirectives(element.dataset.model);

    directives.forEach((directive) => {
        if (directive.args.length > 0 || directive.named.length > 0) {
            throw new Error(
                `The data-model="${element.dataset.model}" format is invalid: it does not support passing arguments to the model.`
            );
        }

        directive.action = normalizeModelName(directive.action);
    });

    return directives;
}

export function getModelDirectiveFromElement(element: HTMLElement, throwOnMissing = true): null | Directive {
    const dataModelDirectives = getAllModelDirectiveFromElements(element);
    if (dataModelDirectives.length > 0) {
        return dataModelDirectives[0];
    }

    if (element.getAttribute('name')) {
        const formElement = element.closest('form');
        // require a <form data-model="*"> around elements in order to
        // activate automatic "data binding" via the "name" attribute
        if (formElement && 'model' in formElement.dataset) {
            const directives = parseDirectives(formElement.dataset.model || '*');
            const directive = directives[0];

            if (directive.args.length > 0 || directive.named.length > 0) {
                throw new Error(
                    `The data-model="${formElement.dataset.model}" format is invalid: it does not support passing arguments to the model.`
                );
            }

            // use the actual field's name as the "action"
            directive.action = normalizeModelName(element.getAttribute('name') as string);

            return directive;
        }
    }

    if (!throwOnMissing) {
        return null;
    }

    throw new Error(
        `Cannot determine the model name for "${getElementAsTagText(
            element
        )}": the element must either have a "data-model" (or "name" attribute living inside a <form data-model="*">).`
    );
}

/**
 * Does the given element "belong" to the given component.
 *
 * To "belong" the element needs to:
 *      A) Live inside the component element (of course)
 *      B) NOT also live inside a child component
 */
export function elementBelongsToThisComponent(element: Element, component: Component): boolean {
    if (component.element === element) {
        return true;
    }

    if (!component.element.contains(element)) {
        return false;
    }

    let foundChildComponent = false;
    component.getChildren().forEach((childComponent) => {
        if (foundChildComponent) {
            // return early
            return;
        }

        if (childComponent.element === element || childComponent.element.contains(element)) {
            foundChildComponent = true;
        }
    });

    return !foundChildComponent;
}

export function cloneHTMLElement(element: HTMLElement): HTMLElement {
    const newElement = element.cloneNode(true);
    if (!(newElement instanceof HTMLElement)) {
        throw new Error('Could not clone element');
    }

    return newElement;
}

// https://stackoverflow.com/questions/494143/creating-a-new-dom-element-from-an-html-string-using-built-in-dom-methods-or-pro#answer-35385518
export function htmlToElement(html: string): HTMLElement {
    const template = document.createElement('template');
    html = html.trim();
    template.innerHTML = html;

    if (template.content.childElementCount > 1) {
        throw new Error(
            `Component HTML contains ${template.content.childElementCount} elements, but only 1 root element is allowed.`
        );
    }

    const child = template.content.firstElementChild;
    if (!child) {
        throw new Error('Child not found');
    }

    // enforcing this for type simplicity: in practice, this is only use for HTMLElements
    if (!(child instanceof HTMLElement)) {
        throw new Error(`Created element is not an HTMLElement: ${html.trim()}`);
    }

    return child;
}

// Inspired by https://stackoverflow.com/questions/13389751/change-tag-using-javascript
export function cloneElementWithNewTagName(element: Element, newTag: string): HTMLElement {
    const originalTag = element.tagName;
    const startRX = new RegExp('^<' + originalTag, 'i');
    const endRX = new RegExp(originalTag + '>$', 'i');
    const startSubst = '<' + newTag;
    const endSubst = newTag + '>';

    const newHTML = element.outerHTML.replace(startRX, startSubst).replace(endRX, endSubst);

    return htmlToElement(newHTML);
}

/**
 * Returns just the outer element's HTML as a string - useful for error messages.
 *
 * For example:
 *      <div class="outer">And text inside <p>more text</p></div>
 *
 * Would return:
 *      <div class="outer">
 */
export function getElementAsTagText(element: HTMLElement): string {
    return element.innerHTML
        ? element.outerHTML.slice(0, element.outerHTML.indexOf(element.innerHTML))
        : element.outerHTML;
}

const getMultipleCheckboxValue = function (element: HTMLInputElement, currentValues: Array<string>): Array<string> {
    const value = inputValue(element);
    const index = currentValues.indexOf(value);

    if (element.checked) {
        // Add value to an array if it's not in it already
        if (index === -1) {
            currentValues.push(value);
        }

        return currentValues;
    }

    // Remove value from an array
    if (index > -1) {
        currentValues.splice(index, 1);
    }

    return currentValues;
};

const inputValue = function (element: HTMLInputElement): string {
    return element.dataset.value ? element.dataset.value : element.value;
};
