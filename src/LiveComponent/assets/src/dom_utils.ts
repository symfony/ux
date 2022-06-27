import ValueStore from './ValueStore';
import { Directive, parseDirectives } from './directives_parser';
import { LiveController } from './live_controller';
import { normalizeModelName } from './string_utils';

/**
 * Return the "value" of any given element.
 *
 * This takes into account that the element may be a "multiple"
 * value input, like an <input type="checkbox"> where there are multiple
 * elements. In those cases, it will return the "full", final value
 * for the model, which includes previously-selected values.
 */
export function getValueFromInput(element: HTMLElement, valueStore: ValueStore): string|string[]|null {
    if (element instanceof HTMLInputElement) {
        if (element.type === 'checkbox') {
            const modelNameData = getModelDirectiveFromInput(element);
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
            return Array.from(element.selectedOptions).map(el => el.value);
        }

        return element.value;
    }

    // element is some other element
    if (element.dataset.value) {
        return element.dataset.value;
    }

    // e.g. a textarea
    if ('value' in element) {
        // the "as" is a cheap way to hint to TypeScript that the value propery exists
        return (element as HTMLInputElement).value;
    }

    if (element.hasAttribute('value')) {
        return element.getAttribute('value');
    }

    return null;
}

export function getModelDirectiveFromInput(element: HTMLElement, throwOnMissing = true): null|Directive {
    if (element.dataset.model) {
        const directives = parseDirectives(element.dataset.model);
        const directive = directives[0];

        if (directive.args.length > 0 || directive.named.length > 0) {
            throw new Error(`The data-model="${element.dataset.model}" format is invalid: it does not support passing arguments to the model.`);
        }

        directive.action = normalizeModelName(directive.action);

        return directive;
    }

    if (element.getAttribute('name')) {
        const formElement = element.closest('form');
        // require a <form data-model="*"> around elements in order to
        // activate automatic "data binding" via the "name" attribute
        if (formElement && formElement.dataset.model) {
            const directives = parseDirectives(formElement.dataset.model);
            const directive = directives[0];

            if (directive.args.length > 0 || directive.named.length > 0) {
                throw new Error(`The data-model="${formElement.dataset.model}" format is invalid: it does not support passing arguments to the model.`);
            }

            // use the actual field's name as the "action"
            directive.action = normalizeModelName(element.getAttribute('name') as string);

            return directive;
        }
    }

    if (!throwOnMissing) {
        return null;
    }

    throw new Error(`Cannot determine the model name for "${getElementAsTagText(element)}": the element must either have a "data-model" (or "name" attribute living inside a <form data-model="*">).`);
}

/**
 * Does the given element "belong" to the given live controller.
 *
 * To "belong" the element needs to:
 *      A) Live inside the controller element (of course)
 *      B) NOT also live inside a child "live controller" element
 */
export function elementBelongsToThisController(element: Element, controller: LiveController): boolean {
    if (controller.element !== element && !controller.element.contains(element)) {
        return false;
    }

    let foundChildController = false;
    controller.childComponentControllers.forEach((childComponentController) => {
        if (foundChildController) {
            // return early
            return;
        }

        if (childComponentController.element === element || childComponentController.element.contains(element)) {
            foundChildController = true;
        }
    });

    return !foundChildController;
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

    const child = template.content.firstChild;
    if (!child) {
        throw new Error('Child not found');
    }

    // enforcing this for type simplicity: in practice, this is only use for HTMLElements
    if (!(child instanceof HTMLElement)) {
        throw new Error(`Created element is not an Element from HTML: ${html.trim()}`);
    }

    return child;
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
    return element.innerHTML ? element.outerHTML.slice(0, element.outerHTML.indexOf(element.innerHTML)) : element.outerHTML;
}

const getMultipleCheckboxValue = function(element: HTMLInputElement, currentValues: Array<string>): Array<string> {
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
}

const inputValue = function(element: HTMLInputElement): string {
    return element.dataset.value ? element.dataset.value : element.value;
}
