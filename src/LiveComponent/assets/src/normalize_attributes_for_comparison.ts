import { cloneHTMLElement } from './dom_utils';

// their "value" only mirrors an attribute or the content, which isEqualNode() already compared
const REFLECTED_VALUE_TAG_NAMES = new Set(['BUTTON', 'DATA', 'LI', 'METER', 'OPTION', 'PROGRESS']);

/**
 * Updates an HTML node to represent its underlying data.
 *
 * For example, this finds the value property of each underlying node
 * and sets that onto the value attribute. This is useful to compare
 * if two nodes are identical.
 */
export function normalizeAttributesForComparison(element: HTMLElement): void {
    const isFileInput = element instanceof HTMLInputElement && element.type === 'file';

    // don't add value attribute to file inputs
    // if a file is selected, but then a re-render happens before it is
    // uploaded, we do NOT want to add a value="" attribute because
    // this would cause the input to re-render (without the attached file)
    if (!isFileInput) {
        if ('value' in element) {
            element.setAttribute('value', (element as HTMLInputElement).value);
        } else if (element.hasAttribute('value')) {
            element.setAttribute('value', '');
        }
    }

    for (let child = element.firstElementChild; child !== null; child = child.nextElementSibling) {
        normalizeAttributesForComparison(child as HTMLElement);
    }
}

/**
 * Only valid when fromEl.isEqualNode(toEl) already holds.
 */
export function areNodesEqualAfterNormalization(fromEl: HTMLElement, toEl: HTMLElement): boolean {
    const comparison = compareFieldValues(fromEl, toEl);
    if (comparison !== null) {
        return comparison;
    }

    const normalizedFromEl = cloneHTMLElement(fromEl);
    normalizeAttributesForComparison(normalizedFromEl);

    const normalizedToEl = cloneHTMLElement(toEl);
    normalizeAttributesForComparison(normalizedToEl);

    return normalizedFromEl.isEqualNode(normalizedToEl);
}

const compareFieldValues = (fromEl: HTMLElement, toEl: HTMLElement): boolean | null => {
    const fromElements = fromEl.querySelectorAll('*');
    let toElements: NodeListOf<Element> | null = null;

    for (let i = -1; i < fromElements.length; i++) {
        const from: Element = i === -1 ? fromEl : fromElements[i];
        if (!('value' in from) || REFLECTED_VALUE_TAG_NAMES.has(from.tagName)) {
            continue;
        }

        // cloning copies the value of an <input> and a <textarea> only
        const isInput = from instanceof HTMLInputElement;
        if (!isInput && !(from instanceof HTMLTextAreaElement)) {
            return null;
        }

        if (isInput && from.type === 'file') {
            continue;
        }

        if (toElements === null) {
            toElements = toEl.querySelectorAll('*');
        }

        const to = (i === -1 ? toEl : toElements[i]) as HTMLInputElement;
        if (from.value !== to.value) {
            return false;
        }
    }

    return true;
};
