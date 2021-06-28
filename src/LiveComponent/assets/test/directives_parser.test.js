import { parseDirectives } from '../src/directives_parser';

const assertDirectiveEquals = function(actual, expected) {
    delete actual.getString;

    expect(actual).toEqual(expected);
}

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
            named: {},
            modifiers: [],
        })
    });

    it('parses an action with a simple argument', () => {
        const directives = parseDirectives('addClass(opacity-50)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['opacity-50'],
            named: {},
            modifiers: [],
        })
    });

    it('parses an action with one argument with a space', () => {
        const directives = parseDirectives('addClass(opacity-50 disabled)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['opacity-50 disabled'],
            named: {},
            modifiers: [],
        })
    });

    it('parses an action with multiple, unnamed arguments', () => {
        const directives = parseDirectives('addClass(opacity-50, disabled)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            // space between arguments is trimmed
            args: ['opacity-50', 'disabled'],
            named: {},
            modifiers: [],
        })
    });

    it('parses multiple actions simple', () => {
        const directives = parseDirectives('addClass(opacity-50) addAttribute(disabled)');
        expect(directives).toHaveLength(2);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['opacity-50'],
            named: {},
            modifiers: [],
        })
        assertDirectiveEquals(directives[1], {
            action: 'addAttribute',
            args: ['disabled'],
            named: {},
            modifiers: [],
        })
    });

    it('parses multiple actions with multiple arguments', () => {
        const directives = parseDirectives('hide addClass(opacity-50 disabled) addAttribute(disabled)');
        expect(directives).toHaveLength(3);
        assertDirectiveEquals(directives[0], {
            action: 'hide',
            args: [],
            named: {},
            modifiers: [],
        })
        assertDirectiveEquals(directives[1], {
            action: 'addClass',
            args: ['opacity-50 disabled'],
            named: {},
            modifiers: [],
        })
        assertDirectiveEquals(directives[2], {
            action: 'addAttribute',
            args: ['disabled'],
            named: {},
            modifiers: [],
        })
    });

    it('parses single named argument', () => {
        const directives = parseDirectives('save(foo=bar)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'save',
            args: [],
            named: { foo: 'bar' },
            modifiers: [],
        })
    });

    it('parses multiple named arguments', () => {
        const directives = parseDirectives('save(foo=bar, baz=bazzles)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'save',
            args: [],
            named: { foo: 'bar', baz: 'bazzles' },
            modifiers: [],
        })
    });

    it('parses arguments and spaces are kept', () => {
        const directives = parseDirectives('save(foo= bar)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'save',
            args: [],
            named: { foo: ' bar' },
            modifiers: [],
        })
    });

    it('parses argument names with space is trimmed', () => {
        const directives = parseDirectives('save(foo  =bar)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'save',
            args: [],
            named: { foo: 'bar' },
            modifiers: [],
        })
    });

    it('parses simple modifiers', () => {
        const directives = parseDirectives('delay|addClass(disabled)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['disabled'],
            named: {},
            modifiers: [
                { name: 'delay', value: null }
            ],
        })
    });

    it('parses modifiers with argument', () => {
        const directives = parseDirectives('delay(400)|addClass(disabled)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'addClass',
            args: ['disabled'],
            named: {},
            modifiers: [
                { name: 'delay', value: '400' },
            ],
        })
    });

    it('parses multiple modifiers', () => {
        const directives = parseDirectives('prevent|debounce(400)|save(foo=bar)');
        expect(directives).toHaveLength(1);
        assertDirectiveEquals(directives[0], {
            action: 'save',
            args: [],
            named: { foo: 'bar' },
            modifiers: [
                { name: 'prevent', value: null },
                { name: 'debounce', value: '400' },
            ],
        })
    });

    describe('errors on syntax errors', () => {
        it('missing ending )', () => {
            expect(() => {
                parseDirectives('addClass(opacity-50');
            }).toThrow('Did you forget to add a closing ")" after "addClass"?')
        });

        it('missing ending before next action', () => {
            expect(() => {
                parseDirectives('addClass(opacity-50 hide');
            }).toThrow('Did you forget to add a closing ")" after "addClass"?')
        });

        it('no space between actions', () => {
            expect(() => {
                parseDirectives('addClass(opacity-50)hide');
            }).toThrow('Missing space after addClass()')
        });

        it('named and unnamed arguments cannot be mixed', () => {
            expect(() => {
                parseDirectives('save(foo=bar, baz)');
            }).toThrow('Normal and named arguments cannot be mixed inside "save()"')
        });

        it('modifier cannot have multiple arguments', () => {
            expect(() => {
                parseDirectives('debounce(10, 20)|save');
            }).toThrow('The modifier "debounce()" does not support multiple arguments.')
        });

        it('modifier cannot have multiple arguments', () => {
            expect(() => {
                parseDirectives('debounce(foo=bar)|save');
            }).toThrow('The modifier "debounce()" does not support named arguments.')
        });
    });
});
