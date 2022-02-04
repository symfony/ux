import { normalizeAttributesForComparison } from '../src/normalize_attributes_for_comparison';

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

describe('normalizeAttributesForComparison', () => {
    it('makes no changes if value and attribute not set', () => {
        const element = createElement('<div class="foo"></div>');
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<div class="foo"></div>');
    });

    it('sets the attribute if the value is present', () => {
        const element = createElement('<input class="foo">');
        element.value = 'set value';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<input class="foo" value="set value">');
    });

    it('sets the attribute to empty if the value is empty', () => {
        const element = createElement('<input class="foo" value="starting">');
        element.value = '';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<input class="foo" value="">');
    });

    it('changes the value attribute if value is different', () => {
        const element = createElement('<input class="foo" value="starting">');
        element.value = 'changed';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<input class="foo" value="changed">');
    });

    it('changes the value attribute on a child', () => {
        const element = createElement('<div><input id="child" value="original"></div>');
        element.querySelector('#child').value = 'changed';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<div><input id="child" value="changed"></div>');
    });

    it('changes the value on multiple levels', () => {
        const element = createElement('<div><input id="child" value="original"><div><input id="grand_child"></div></div>');
        element.querySelector('#child').value = 'changed';
        element.querySelector('#grand_child').value = 'changed grand child';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<div><input id="child" value="changed"><div><input id="grand_child" value="changed grand child"></div></div>');
    });
});
