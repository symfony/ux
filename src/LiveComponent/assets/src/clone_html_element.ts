export function cloneHTMLElement(element: HTMLElement): HTMLElement {
    const newElement = element.cloneNode(true);
    if (!(newElement instanceof HTMLElement)) {
        throw new Error('Could not clone element');
    }

    return newElement;
}
