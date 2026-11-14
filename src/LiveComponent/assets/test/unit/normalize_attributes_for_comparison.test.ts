import { describe, expect, it } from 'vitest';
import { cloneHTMLElement, htmlToElement } from '../../src/dom_utils';
import {
    areNodesEqualAfterNormalization,
    normalizeAttributesForComparison,
} from '../../src/normalize_attributes_for_comparison';

describe('normalizeAttributesForComparison', () => {
    it('makes no changes if value and attribute not set', () => {
        const element = htmlToElement('<div class="foo"></div>');
        normalizeAttributesForComparison(element);
        expect(element.outerHTML).toEqual('<div class="foo"></div>');
    });

    it('sets the attribute if the value is present', () => {
        const element = htmlToElement('<input class="foo">') as HTMLInputElement;
        element.value = 'set value';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML).toEqual('<input class="foo" value="set value">');
    });

    it('sets the attribute to empty if the value is empty', () => {
        const element = htmlToElement('<input class="foo" value="starting">') as HTMLInputElement;
        element.value = '';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML).toEqual('<input class="foo" value="">');
    });

    it('changes the value attribute if value is different', () => {
        const element = htmlToElement('<input class="foo" value="starting">') as HTMLInputElement;
        element.value = 'changed';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML).toEqual('<input class="foo" value="changed">');
    });

    it('changes the value attribute on a child', () => {
        const element = htmlToElement('<div><input id="child" value="original"></div>');
        (element.querySelector('#child') as HTMLInputElement).value = 'changed';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML).toEqual('<div><input id="child" value="changed"></div>');
    });

    it('changes the value on multiple levels', () => {
        const element = htmlToElement(
            '<div><input id="child" value="original"><div><input id="grand_child"></div></div>'
        );
        (element.querySelector('#child') as HTMLInputElement).value = 'changed';
        (element.querySelector('#grand_child') as HTMLInputElement).value = 'changed grand child';
        normalizeAttributesForComparison(element);
        expect(element.outerHTML).toEqual(
            '<div><input id="child" value="changed"><div><input id="grand_child" value="changed grand child"></div></div>'
        );
    });
});

describe('areNodesEqualAfterNormalization', () => {
    /** The clone-based comparison this function must keep answering exactly like. */
    const compareWithClones = (fromEl: HTMLElement, toEl: HTMLElement): boolean => {
        const normalizedFromEl = cloneHTMLElement(fromEl);
        normalizeAttributesForComparison(normalizedFromEl);

        const normalizedToEl = cloneHTMLElement(toEl);
        normalizeAttributesForComparison(normalizedToEl);

        return normalizedFromEl.isEqualNode(normalizedToEl);
    };

    class ValueCustomElement extends HTMLElement {
        private innerValue = '';

        get value(): string {
            return this.innerValue;
        }

        set value(value: string) {
            this.innerValue = value;
        }
    }

    if (!customElements.get('value-custom-element')) {
        customElements.define('value-custom-element', ValueCustomElement);
    }

    const cases: Array<[string, string, (element: HTMLElement) => void, boolean]> = [
        ['a subtree with no field', '<div class="a"><span>text</span></div>', () => {}, true],
        [
            'a "value" attribute on an element that has no value property',
            '<div><p value="7">text</p></div>',
            () => {},
            true,
        ],
        ['an untouched text input', '<div><input type="text" value="a"></div>', () => {}, true],
        [
            'a text input the user typed into',
            '<div><input type="text" value="a"></div>',
            (element) => {
                (element.querySelector('input') as HTMLInputElement).value = 'typed';
            },
            false,
        ],
        ['an untouched textarea', '<div><textarea>a</textarea></div>', () => {}, true],
        [
            'a textarea the user typed into',
            '<div><textarea>a</textarea></div>',
            (element) => {
                (element.querySelector('textarea') as HTMLTextAreaElement).value = 'typed';
            },
            false,
        ],
        [
            'a checkbox the user checked',
            '<div><input type="checkbox" value="5"></div>',
            (element) => {
                (element.querySelector('input') as HTMLInputElement).checked = true;
            },
            true,
        ],
        ['an untouched file input', '<div><input type="file"></div>', () => {}, true],
        [
            'a file input next to a text input the user typed into',
            '<div><input type="file"><input type="text" value="a"></div>',
            (element) => {
                (element.querySelector('input[type=text]') as HTMLInputElement).value = 'typed';
            },
            false,
        ],
        [
            'an untouched select',
            '<div><select><option value="a" selected>A</option><option value="b">B</option></select></div>',
            () => {},
            true,
        ],
        [
            'a select the user changed',
            '<div><select><option value="a" selected>A</option><option value="b">B</option></select></div>',
            (element) => {
                (element.querySelector('select') as HTMLSelectElement).value = 'b';
            },
            true,
        ],
        [
            'a select next to a text input the user typed into',
            '<div><select><option value="a" selected>A</option></select><input type="text" value="a"></div>',
            (element) => {
                (element.querySelector('input') as HTMLInputElement).value = 'typed';
            },
            false,
        ],
        [
            'a custom element whose value property changed',
            '<div><value-custom-element></value-custom-element></div>',
            (element) => {
                (element.querySelector('value-custom-element') as ValueCustomElement).value = 'typed';
            },
            true,
        ],
        ['an element whose value property mirrors its attribute', '<div><li value="3">x</li></div>', () => {}, true],
        ['the node itself being an untouched input', '<input type="text" value="a">', () => {}, true],
        [
            'the node itself being an input the user typed into',
            '<input type="text" value="a">',
            (element) => {
                (element as HTMLInputElement).value = 'typed';
            },
            false,
        ],
        [
            'a text input deep inside a table the user typed into',
            '<table><tbody><tr><td><input type="text" value="a"></td></tr><tr><td><input type="text" value="b"></td></tr></tbody></table>',
            (element) => {
                (element.querySelectorAll('input')[1] as HTMLInputElement).value = 'typed';
            },
            false,
        ],
    ];

    it.each(cases)('answers %s', (_label, html, mutate, expected) => {
        const fromEl = htmlToElement(html);
        const toEl = htmlToElement(html);
        mutate(fromEl);

        expect(fromEl.isEqualNode(toEl)).toBe(true);
        expect(areNodesEqualAfterNormalization(fromEl, toEl)).toBe(expected);
    });

    it.each(cases)('answers %s exactly like the clone-based comparison', (_label, html, mutate) => {
        const fromEl = htmlToElement(html);
        const toEl = htmlToElement(html);
        mutate(fromEl);

        expect(areNodesEqualAfterNormalization(fromEl, toEl)).toBe(compareWithClones(fromEl, toEl));
    });
});
