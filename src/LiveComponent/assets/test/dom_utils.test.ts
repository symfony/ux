import {
    getValueFromElement,
    cloneHTMLElement,
    htmlToElement,
    getModelDirectiveFromElement,
    elementBelongsToThisComponent,
    getElementAsTagText,
    setValueOnElement
} from '../src/dom_utils';
import ValueStore from '../src/Component/ValueStore';
import Component from '../src/Component';
import Backend from '../src/Backend/Backend';
import {StandardElementDriver} from '../src/Component/ElementDriver';

const createStore = function(props: any = {}): ValueStore {
    return new ValueStore(props);
}

describe('getValueFromElement', () => {
    it('Correctly adds data from valued checked checkbox', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = true;
        input.dataset.model = 'foo';
        input.value = 'the_checkbox_value';

        expect(getValueFromElement(input, createStore()))
            .toEqual('the_checkbox_value');

        expect(getValueFromElement(input, createStore({ foo: [] })))
            .toEqual(['the_checkbox_value']);

        expect(getValueFromElement(input, createStore({ foo: ['bar'] })))
            .toEqual(['bar', 'the_checkbox_value']);
    });

    it('Correctly removes data from valued unchecked checkbox', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = false;
        input.dataset.model = 'foo';
        input.value = 'the_checkbox_value';

        expect(getValueFromElement(input, createStore()))
            .toEqual(null);
        expect(getValueFromElement(input, createStore({ foo: ['the_checkbox_value'] })))
            .toEqual([]);
        // unchecked value already was not in store
        expect(getValueFromElement(input, createStore({ foo: ['bar'] })))
            .toEqual(['bar']);
        expect(getValueFromElement(input, createStore({ foo: ['bar', 'the_checkbox_value'] })))
            .toEqual(['bar']);
    });

    it('Correctly handles boolean checkbox', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = true;
        input.dataset.model = 'foo';

        expect(getValueFromElement(input, createStore()))
            .toEqual(true);

        input.checked = false;

        expect(getValueFromElement(input, createStore()))
            .toEqual(false);
    });

    it('Correctly returns for non-model checkboxes', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = true;
        input.value = 'the_checkbox_value';

        expect(getValueFromElement(input, createStore()))
            .toEqual('the_checkbox_value');

        input.checked = false;
        expect(getValueFromElement(input, createStore()))
            .toEqual(null);
    });

    it('Correctly sets data from select multiple', () => {
        const select = document.createElement('select');
        select.multiple = true;

        const fooOption = document.createElement('option');
        fooOption.value = 'foo';
        select.add(fooOption);
        const barOption = document.createElement('option');
        barOption.value = 'bar';
        select.add(barOption);

        // nothing selected
        expect(getValueFromElement(select, createStore()))
            .toEqual([]);

        fooOption.selected = true;
        expect(getValueFromElement(select, createStore()))
            .toEqual(['foo']);

        barOption.selected = true;
        expect(getValueFromElement(select, createStore()))
            .toEqual(['foo', 'bar']);
    })

    it('Grabs data-value attribute for other elements', () => {
        const div = document.createElement('div');
        div.dataset.value = 'the_value';

        expect(getValueFromElement(div, createStore()))
            .toEqual('the_value');
    });

    it('Grabs value attribute for other elements', () => {
        const div = document.createElement('div');
        div.setAttribute('value', 'the_value_from_attribute');

        expect(getValueFromElement(div, createStore()))
            .toEqual('the_value_from_attribute');
    });
});

describe('setValueOnElement', () => {
    it('Checks checkbox with scalar value', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.value = 'the_checkbox_value';

        setValueOnElement(input, 'the_checkbox_value');
        expect(input.checked).toBeTruthy();
    });

    it('Checks checkbox with array value', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.value = 'the_checkbox_value';

        setValueOnElement(input, ['dinosaur', 'the_checkbox_value']);
        expect(input.checked).toBeTruthy();
    });

    it('Unchecks checkbox with scalar value', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = true;
        input.value = 'the_checkbox_value';

        setValueOnElement(input, 'other_value');
        expect(input.checked).toBeFalsy();
    });

    it('Unchecks checkbox with array value', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = true;
        input.value = 'the_checkbox_value';

        setValueOnElement(input, ['other_value', 'foo']);
        expect(input.checked).toBeFalsy();
    });

    it('Checks checkbox with boolean value', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = false;

        setValueOnElement(input, true);
        expect(input.checked).toBeTruthy();
    });

    it('Unchecks checkbox with boolean value', () => {
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.checked = true;

        setValueOnElement(input, false);
        expect(input.checked).toBeFalsy();
    });

    it('Sets data onto select multiple', () => {
        const select = document.createElement('select');
        select.multiple = true;

        const fooOption = document.createElement('option');
        fooOption.value = 'foo';
        select.add(fooOption);
        const barOption = document.createElement('option');
        barOption.value = 'bar';
        select.add(barOption);

        setValueOnElement(select, ['not matching']);
        expect(fooOption.selected).toBeFalsy();
        expect(barOption.selected).toBeFalsy();

        setValueOnElement(select, ['bar']);
        expect(fooOption.selected).toBeFalsy();
        expect(barOption.selected).toBeTruthy();

        setValueOnElement(select, ['bar', 'foo']);
        expect(fooOption.selected).toBeTruthy();
        expect(barOption.selected).toBeTruthy();

        setValueOnElement(select, ['foo']);
        expect(fooOption.selected).toBeTruthy();
        expect(barOption.selected).toBeFalsy();
    })

    it('Sets value on other elements', () => {
        const input = document.createElement('input');
        input.value = 'some thing else';

        setValueOnElement(input, 'new value');

        expect(input.value).toEqual('new value');
    });
});

describe('getModelDirectiveFromInput', () => {
    it('reads data-model correctly', () => {
        const element = htmlToElement('<input data-model="firstName">');

        const directive = getModelDirectiveFromElement(element);
        expect(directive).not.toBeNull();
        expect(directive?.action).toBe('firstName');
    });

    it('reads data-model normalized name', () => {
        const element = htmlToElement('<input data-model="user[firstName]">');

        const directive = getModelDirectiveFromElement(element);
        expect(directive).not.toBeNull();
        expect(directive?.action).toBe('user.firstName');
    });

    it('reads name attribute if form[data-model] is present', () => {
        const formElement = htmlToElement('<form data-model="*"></form>');
        const element = htmlToElement('<input name="user[firstName]">');
        formElement.appendChild(element);

        const directive = getModelDirectiveFromElement(element);
        expect(directive).not.toBeNull();
        expect(directive?.action).toBe('user.firstName');
    });

    it('does NOT reads name attribute if form[data-model] is NOT present', () => {
        const formElement = htmlToElement('<form></form>');
        const element = htmlToElement('<input name="user[firstName]">');
        formElement.appendChild(element);

        const directive = getModelDirectiveFromElement(element, false);
        expect(directive).toBeNull();
    });

    it('throws error if no data-model found', () => {
        const element = htmlToElement('<input>');

        expect(() => { getModelDirectiveFromElement(element) }).toThrow('Cannot determine the model name');
    });
});

describe('elementBelongsToThisComponent', () => {
    const createComponent = (html: string, childComponents: Component[] = []) => {
        const component = new Component(
            htmlToElement(html),
            'some-component',
            {},
            [],
            () => [],
            null,
            'some-id-' + Math.floor((Math.random() * 100)),
            new Backend(''),
            new StandardElementDriver()
        );
        childComponents.forEach((childComponent) => {
            component.addChild(childComponent);
        })

        return component;
    };

    it('returns false if element lives outside of controller', () => {
        const targetElement = htmlToElement('<input name="user[firstName]">');
        const component = createComponent('<div></div>');

        expect(elementBelongsToThisComponent(targetElement, component)).toBeFalsy();
    });

    it('returns true if element lives inside of controller', () => {
        const targetElement = htmlToElement('<input name="user[firstName]">');
        const component = createComponent('<div></div>');
        component.element.appendChild(targetElement);

        expect(elementBelongsToThisComponent(targetElement, component)).toBeTruthy();
    });

    it('returns false if element lives inside of child controller', () => {
        const targetElement = htmlToElement('<input name="user[firstName]">');
        const childComponent = createComponent('<div class="child"></div>');
        childComponent.element.appendChild(targetElement);

        const component = createComponent('<div class="parent"></div>', [childComponent]);
        component.element.appendChild(childComponent.element);

        expect(elementBelongsToThisComponent(targetElement, childComponent)).toBeTruthy();
        expect(elementBelongsToThisComponent(targetElement, component)).toBeFalsy();
    });

    it('returns false if element *is* a child controller element', () => {
        const childComponent = createComponent('<div class="child"></div>');

        const component = createComponent('<div class="parent"></div>', [childComponent]);
        component.element.appendChild(childComponent.element);

        expect(elementBelongsToThisComponent(childComponent.element, component)).toBeFalsy();
    });
});

describe('getElementAsTagText', () => {
    it('returns self-closing tag correctly', () => {
        const element = htmlToElement('<input name="user[firstName]">');

        expect(getElementAsTagText(element)).toEqual('<input name="user[firstName]">')
    });

    it('returns tag text without the innerHTML', () => {
        const element = htmlToElement('<div class="foo">Name: <input name="user[firstName]"></div>');

        expect(getElementAsTagText(element)).toEqual('<div class="foo">')
    });
});

describe('htmlToElement', () => {
    it('allows to clone HTMLElement', () => {
        const element = htmlToElement('<div class="foo">bar</div>');
        expect(element).toBeInstanceOf(HTMLElement);
        expect(element.outerHTML).toEqual('<div class="foo">bar</div>');
    });
});

describe('cloneHTMLElement', () => {
    it('allows to clone HTMLElement', () => {
        const element = htmlToElement('<div class="foo"></div>');
        const clone = cloneHTMLElement(element);

        expect(clone.outerHTML).toEqual('<div class="foo"></div>');
    });
});
