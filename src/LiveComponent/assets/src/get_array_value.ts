/**
 * Resolve multiple value data from changed HTML element.
 *
 * @param element       Current HTML element
 * @param value         Resolved value of a single HTML element (.value or [data-value])
 * @param currentValue  Current data value
 */
export function getArrayValue(
    element: HTMLElement,
    value: string|null,
    currentValue: any
): Array<string>|null {
    // Enforce returned value is an array
    if (!(currentValue instanceof Array)) {
        currentValue = [];
    }

    if (element instanceof HTMLInputElement && element.type === 'checkbox') {
        const index = currentValue.indexOf(value);

        if (element.checked) {
            // Add value to an array if it's not in it already
            if (index === -1) {
                currentValue.push(value);
            }
        } else {
            // Remove value from an array
            if (index > -1) {
                currentValue.splice(index, 1);
            }
        }
    } else if (element instanceof HTMLSelectElement) {
        // Select elements with `multiple` option require mapping chosen options to their values
        currentValue = Array.from(element.selectedOptions).map(el => el.value);
    }

    // When no values are selected for collection no data should be sent over the wire
    return currentValue.length ? currentValue : null;
}
