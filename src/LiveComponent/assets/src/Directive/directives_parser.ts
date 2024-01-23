/**
 * A modifier for a directive
 */
export interface DirectiveModifier {
    /**
     * The name of the modifier (e.g. delay)
     */
    name: string;

    /**
     * The value of the single argument or null if no argument
     */
    value: string | null;
}

/**
 * A directive with action, args and modifiers.
 */
export interface Directive {
    /**
     * The name of the action (e.g. addClass)
     */
    action: string;
    /**
     * An array of unnamed arguments passed to the action
     */
    args: string[];
    /**
     * Any modifiers applied to the action
     */
    modifiers: DirectiveModifier[];
    getString: { (): string };
}

/**
 * Parses strings like "addClass(foo) removeAttribute(bar)"
 * into an array of directives, with this format:
 *
 *      [
 *          { action: 'addClass', args: ['foo'], modifiers: [] },
 *          { action: 'removeAttribute', args: ['bar'], modifiers: [] }
 *      ]
 *
 * @param {string} content The value of the attribute
 */
export function parseDirectives(content: string|null): Directive[] {
    const directives: Directive[] = [];

    if (!content) {
        return directives;
    }

    let currentActionName = '';
    let currentArgumentValue = '';
    let currentArguments: string[] = [];
    let currentModifiers: { name: string, value: string | null }[] = [];
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
            modifiers: currentModifiers,
            getString: () => {
                // TODO - make a string representation of JUST this directive

                return content;
            }
        });
        currentActionName = '';
        currentArgumentValue = '';
        currentArguments = [];
        currentModifiers = [];
        state = 'action';
    }
    const pushArgument = function() {
        // value is trimmed to avoid space after ","
        // "foo, bar"
        currentArguments.push(currentArgumentValue.trim());
        currentArgumentValue = '';
    }

    const pushModifier = function() {
        if (currentArguments.length > 1) {
            throw new Error(`The modifier "${currentActionName}()" does not support multiple arguments.`)
        }

        currentModifiers.push({
            name: currentActionName,
            value: currentArguments.length > 0 ? currentArguments[0] : null,
        });
        currentActionName = '';
        currentArguments = [];
        state = 'action';
    }

    for (let i = 0; i < content.length; i++) {
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
