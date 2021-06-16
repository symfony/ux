/**
 * A modifier for a directive
 *
 * @typedef {Object} DirectiveModifier
 * @property {string} name The name of the modifier (e.g. delay)
 * @property {string|null} value The value of the single argument or null if no argument
 */

/**
 * A directive with action, args and modifiers.
 *
 * @typedef {Object} Directive
 * @property {string} action The name of the action (e.g. addClass)
 * @property {string[]} args An array of unnamed arguments passed to the action
 * @property {Object} named An object of named arguments
 * @property {DirectiveModifier[]} modifiers Any modifiers applied to the action
 * @property {function} getString()
 */

/**
 * Parses strings like "addClass(foo) removeAttribute(bar)"
 * into an array of directives, with this format:
 *
 *      [
 *          { action: 'addClass', args: ['foo'], named: {}, modifiers: [] },
 *          { action: 'removeAttribute', args: ['bar'], named: {}, modifiers: [] }
 *      ]
 *
 * This also handles named arguments
 *
 *      save(foo=bar, baz=bazzles)
 *
 * Which would return:
 *      [
 *          { action: 'save', args: [], named: { foo: 'bar', baz: 'bazzles }, modifiers: [] }
 *      ]
 *
 * @param {string} content The value of the attribute
 * @return {Directive[]}
 */
export function parseDirectives(content) {
    const directives = [];

    if (!content) {
        return directives;
    }

    let currentActionName = '';
    let currentArgumentName = '';
    let currentArgumentValue = '';
    let currentArguments = [];
    let currentNamedArguments = {};
    let currentModifiers = [];
    let state = 'action';

    const getLastActionName = function() {
        if (currentActionName) {
            return currentActionName;
        }

        if (directives.length === 0) {
            throw new Error('Could not find any directives');
        }

        return directives[directives.length - 1].action;
    }
    const pushInstruction = function() {
        directives.push({
            action: currentActionName,
            args: currentArguments,
            named: currentNamedArguments,
            modifiers: currentModifiers,
            getString: () => {
                // TODO - make a string representation of JUST this directive

                return content;
            }
        });
        currentActionName = '';
        currentArgumentName = '';
        currentArgumentValue = '';
        currentArguments = [];
        currentNamedArguments = {};
        currentModifiers = [];
        state = 'action';
    }
    const pushArgument = function() {
        const mixedArgTypesError = () => {
            throw new Error(`Normal and named arguments cannot be mixed inside "${currentActionName}()"`)
        }

        if (currentArgumentName) {
            if (currentArguments.length > 0) {
                mixedArgTypesError();
            }

            // argument names are also trimmed to avoid space after ","
            // "foo=bar, baz=bazzles"
            currentNamedArguments[currentArgumentName.trim()] = currentArgumentValue;
        } else {
            if (Object.keys(currentNamedArguments).length > 0) {
                mixedArgTypesError();
            }

            // value is trimmed to avoid space after ","
            // "foo, bar"
            currentArguments.push(currentArgumentValue.trim());
        }
        currentArgumentName = '';
        currentArgumentValue = '';
    }

    const pushModifier = function() {
        if (currentArguments.length > 1) {
            throw new Error(`The modifier "${currentActionName}()" does not support multiple arguments.`)
        }

        if (Object.keys(currentNamedArguments).length > 0) {
            throw new Error(`The modifier "${currentActionName}()" does not support named arguments.`)
        }

        currentModifiers.push({
            name: currentActionName,
            value: currentArguments.length > 0 ? currentArguments[0] : null,
        });
        currentActionName = '';
        currentArgumentName = '';
        currentArguments = [];
        state = 'action';
    }

    for (var i = 0; i < content.length; i++) {
        const char = content[i];
        switch(state) {
            case 'action':
                if (char === '(') {
                    state = 'arguments';

                    break;
                }

                if (char === ' ') {
                    // this is the end of the action and it has no arguments
                    // if the action had args(), it was already recorded
                    if (currentActionName) {
                        pushInstruction();
                    }

                    break;
                }

                if (char === '|') {
                    // ah, this was a modifier (with no arguments)
                    pushModifier();

                    break;
                }

                // we're expecting more characters for an action name
                currentActionName += char;

                break;

            case 'arguments':
                if (char === ')') {
                    // end of the arguments for a modifier or the action
                    pushArgument();

                    state = 'after_arguments';

                    break;
                }

                if (char === ',') {
                    // end of current argument
                    pushArgument();

                    break;
                }

                if (char === '=') {
                    // this is a named argument!
                    currentArgumentName = currentArgumentValue;
                    currentArgumentValue = '';

                    break;
                }

                // add next character to argument
                currentArgumentValue += char;

                break;

            case 'after_arguments':
                // the previous character was a ")" to end arguments

                // ah, this was actually the end of a modifier!
                if (char === '|') {
                    pushModifier();

                    break;
                }

                // we just finished an action(), and now we need a space
                if (char !== ' ') {
                    throw new Error(`Missing space after ${getLastActionName()}()`)
                }

                pushInstruction();

                break;
        }
    }

    switch (state) {
        case 'action':
        case 'after_arguments':
            if (currentActionName) {
                pushInstruction();
            }

            break;
        default:
            throw new Error(`Did you forget to add a closing ")" after "${currentActionName}"?`)
    }

    return directives;
}
