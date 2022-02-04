/**
 * Updates an HTML node to represent its underlying data.
 *
 * For example, this finds the value property of each underlying node
 * and sets that onto the value attribute. This is useful to compare
 * if two nodes are identical.
 */
export function normalizeAttributesForComparison(element: HTMLElement): void {
    if (element.value) {
        element.setAttribute('value', element.value);
    } else if (element.hasAttribute('value')) {
        element.setAttribute('value', '');
    }

    Array.from(element.children).forEach((child: HTMLElement) => {
        normalizeAttributesForComparison(child);
    });
}
