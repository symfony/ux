import { cloneHTMLElement } from '../src/clone_html_element';

const createElement = function(html: string): HTMLElement {
    const template = document.createElement('template');
    html = html.trim();
    template.innerHTML = html;

    const child = template.content.firstChild;
    if (!child || !(child instanceof HTMLElement)) {
        throw new Error('Child not found');
    }

    return child;
}

describe('cloneHTMLElement', () => {
    it('allows to clone HTMLElement', () => {
        const element = createElement('<div class="foo"></div>');
        const clone = cloneHTMLElement(element);

        expect(clone.outerHTML).toEqual('<div class="foo"></div>');
    });
});
