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
        if (element.value) {
            element.setAttribute('value', element.value);
        } else if (element.hasAttribute('value')) {
            element.setAttribute('value', '');
        }
    }

    Array.from(element.children).forEach((child: HTMLElement) => {
        normalizeAttributesForComparison(child);
    });
}
