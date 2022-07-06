import { normalizeAttributesForComparison } from '../src/normalize_attributes_for_comparison';
import { htmlToElement } from '../src/dom_utils';

describe('normalizeAttributesForComparison', () => {
    it('makes no changes if value and attribute not set', () => {
        const element = htmlToElement('<div class="foo"></div>');
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<div class="foo"></div>');
    });

    it('sets the attribute if the value is present', () => {
        const element = htmlToElement('<input class="foo">') as HTMLInputElement;
        element.value = 'set value';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<input class="foo" value="set value">');
    });

    it('sets the attribute to empty if the value is empty', () => {
        const element = htmlToElement('<input class="foo" value="starting">') as HTMLInputElement;
        element.value = '';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<input class="foo" value="">');
    });

    it('changes the value attribute if value is different', () => {
        const element = htmlToElement('<input class="foo" value="starting">') as HTMLInputElement;
        element.value = 'changed';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<input class="foo" value="changed">');
    });

    it('changes the value attribute on a child', () => {
        const element = htmlToElement('<div><input id="child" value="original"></div>');
        (element.querySelector('#child') as HTMLInputElement).value = 'changed';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<div><input id="child" value="changed"></div>');
    });

    it('changes the value on multiple levels', () => {
        const element = htmlToElement('<div><input id="child" value="original"><div><input id="grand_child"></div></div>');
        (element.querySelector('#child') as HTMLInputElement).value = 'changed';
        (element.querySelector('#grand_child') as HTMLInputElement).value = 'changed grand child';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML)
            .toEqual('<div><input id="child" value="changed"><div><input id="grand_child" value="changed grand child"></div></div>');
    });
});
