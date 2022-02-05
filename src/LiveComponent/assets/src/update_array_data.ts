/**
 * Adds or removes a key from an array element based on an array element.
 *
 * Given an "array" element (e.g. )
 * and the current data for "preferences" (e.g. ["text", "phone"]), this function will add or
 * remove the value (e.g. email) from that array (based on if the element (un)checked) and
 * return the final, updated array (e.g. ["text", "phone", "email"]).
 *
 * @param element       Current HTML element
 * @param value         The value that should be set or removed from currentValues
 * @param currentValues  Current data value
 */
export function updateArrayDataFromChangedElement(
    element: HTMLElement,
    value: string|null,
    currentValues: any
): Array<string> {
    // Enforce returned value is an array
    if (!(currentValues instanceof Array)) {
        currentValues = [];
    }

    if (element instanceof HTMLInputElement && element.type === 'checkbox') {
        const index = currentValues.indexOf(value);

        if (element.checked) {
            // Add value to an array if it's not in it already
            if (index === -1) {
                currentValues.push(value);
            }
        } else {
            // Remove value from an array
            if (index > -1) {
                currentValues.splice(index, 1);
            }
        }
    } else if (element instanceof HTMLSelectElement) {
        // Select elements with `multiple` option require mapping chosen options to their values
        currentValues = Array.from(element.selectedOptions).map(el => el.value);
    }

    // When no values are selected for collection no data should be sent over the wire
    return currentValues;
}
