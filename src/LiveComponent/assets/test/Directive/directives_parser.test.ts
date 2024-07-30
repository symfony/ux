import { type Directive, parseDirectives } from '../../src/Directive/directives_parser';

const assertDirectiveEquals = (actual: Directive, expected: any) => {
    // normalize this so that it doesn't trip up the comparison
    const getString = () => 'foo';
    actual.getString = getString;
    expected.getString = getString;

    expect(actual).toEqual(expected);
};

describe('directives parser', () => {
    it('parses no attribute value', () => {
        // <span data-loading> (no attribute value)
        const directives = parseDirectives(null);
        expect(directives).toHaveLength(0);
    });

    it('parses an empty attribute', () => {
        // <span data-loading="">
        const directives = parseDirectives('');
        expect(directives).toHaveLength(0);
    });

    it('parses a simple action', () => {
        // data-loading="hide"
        const directives = parseDirectives('hide');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'hide',
            args: [],
            modifiers: [],
        });
    });

    it('parses an action with a simple argument', () => {
        const directives = parseDirectives('addClass(opacity-50)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['opacity-50'],
            modifiers: [],
        });
    });

    it('parses an action with one argument with a space', () => {
        const directives = parseDirectives('addClass(opacity-50 disabled)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['opacity-50 disabled'],
            modifiers: [],
        });
    });

    it('parses an action with multiple, unnamed arguments', () => {
        const directives = parseDirectives('addClass(opacity-50, disabled)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            // space between arguments is trimmed
            args: ['opacity-50', 'disabled'],
            modifiers: [],
        });
    });

    it('parses multiple actions simple', () => {
        const directives = parseDirectives('addClass(opacity-50) addAttribute(disabled)');
        expect(directives).toHaveLength(2);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['opacity-50'],
            modifiers: [],
        });
        assertDirectiveEquals(directives[1], {
            action: 'addAttribute',
            args: ['disabled'],
            modifiers: [],
        });
    });

    it('parses multiple actions with multiple arguments', () => {
        const directives = parseDirectives('hide addClass(opacity-50 disabled) addAttribute(disabled)');
        expect(directives).toHaveLength(3);
        assertDirectiveEquals(directives[0], {
            action: 'hide',
            args: [],
            modifiers: [],
        });
        assertDirectiveEquals(directives[1], {
            action: 'addClass',
            args: ['opacity-50 disabled'],
            modifiers: [],
        });
        assertDirectiveEquals(directives[2], {
            action: 'addAttribute',
            args: ['disabled'],
            modifiers: [],
        });
    });

    it('parses simple modifiers', () => {
        const directives = parseDirectives('delay|addClass(disabled)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['disabled'],
            modifiers: [{ name: 'delay', value: null }],
        });
    });

    it('parses modifiers with argument', () => {
        const directives = parseDirectives('delay(400)|addClass(disabled)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['disabled'],
            modifiers: [{ name: 'delay', value: '400' }],
        });
    });

    it('parses multiple modifiers', () => {
        const directives = parseDirectives('prevent|debounce(400)|save');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'save',
            args: [],
            modifiers: [
                { name: 'prevent', value: null },
                { name: 'debounce', value: '400' },
            ],
        });
    });

    describe('errors on syntax errors', () => {
        it('missing ending )', () => {
            expect(() => {
                parseDirectives('addClass(opacity-50');
            }).toThrow('Did you forget to add a closing ")" after "addClass"?');
        });

        it('missing ending before next action', () => {
            expect(() => {
                parseDirectives('addClass(opacity-50 hide');
            }).toThrow('Did you forget to add a closing ")" after "addClass"?');
        });

        it('no space between actions', () => {
            expect(() => {
                parseDirectives('addClass(opacity-50)hide');
            }).toThrow('Missing space after addClass()');
        });

        it('modifier cannot have multiple arguments', () => {
            expect(() => {
                parseDirectives('debounce(10, 20)|save');
            }).toThrow('The modifier "debounce()" does not support multiple arguments.');
        });
    });
});
