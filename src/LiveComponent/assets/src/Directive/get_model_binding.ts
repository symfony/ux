import type {Directive} from './directives_parser';

export interface ModelBinding {
    modelName: string,
    innerModelName: string|null,
    shouldRender: boolean,
    debounce: number|boolean,
    targetEventName: string|null
}

export default function(modelDirective: Directive): ModelBinding {
    let shouldRender = true;
    let targetEventName = null;
    let debounce: number|boolean = false;

    modelDirective.modifiers.forEach((modifier) => {
        switch (modifier.name) {
            case 'on':
                if (!modifier.value) {
                    throw new Error(`The "on" modifier in ${modelDirective.getString()} requires a value - e.g. on(change).`);
                }
                if (!['input', 'change'].includes(modifier.value)) {
                    throw new Error(`The "on" modifier in ${modelDirective.getString()} only accepts the arguments "input" or "change".`);
                }

                targetEventName = modifier.value;

                break;
            case 'norender':
                shouldRender = false;

                break;

            case 'debounce':
                debounce = modifier.value ? Number.parseInt(modifier.value) : true;

                break;
            default:
                throw new Error(`Unknown modifier "${modifier.name}" in data-model="${modelDirective.getString()}".`);
        }
    });

    const [ modelName, innerModelName ] = modelDirective.action.split(':');

    return {
        modelName,
        innerModelName: innerModelName || null,
        shouldRender,
        debounce,
        targetEventName
    }
}
