import { type Formatters, IntlMessageFormat } from 'intl-messageformat';

const instances = new Map<string, Map<string, IntlMessageFormat>>();

function memoize<TArgs extends unknown[], TResult>(create: (...args: TArgs) => TResult): (...args: TArgs) => TResult {
    const cache = new Map<string, TResult>();

    return (...args: TArgs): TResult => {
        const key = JSON.stringify(args);

        let value = cache.get(key);
        if (value === undefined) {
            value = create(...args);
            cache.set(key, value);
        }

        return value;
    };
}

// intl-messageformat memoizes the Intl.* objects per instance: without these, each message builds its own
const formatters: Formatters = {
    getNumberFormat: memoize((locales, options) => new Intl.NumberFormat(locales, options)),
    getDateTimeFormat: memoize((locales, options) => new Intl.DateTimeFormat(locales, options)),
    getPluralRules: memoize((locales, options) => new Intl.PluralRules(locales, options)),
};

/**
 * @private
 *
 * @param id         The message id
 * @param parameters An array of parameters for the message
 * @param locale     The locale
 */
export function formatIntl(id: string, parameters: Record<string, string | number | Date>, locale: string): string {
    if (id === '') {
        return '';
    }

    let instancesByMessage = instances.get(locale);
    if (instancesByMessage === undefined) {
        instancesByMessage = new Map();
        instances.set(locale, instancesByMessage);
    }

    let intlMessage = instancesByMessage.get(id);
    if (intlMessage === undefined) {
        intlMessage = new IntlMessageFormat(id, [locale.replaceAll('_', '-')], undefined, {
            ignoreTag: true,
            formatters,
        });
        instancesByMessage.set(id, intlMessage);
    }

    let values = parameters;

    for (const key of Object.keys(parameters)) {
        if (key.includes('%') || key.includes('{')) {
            if (values === parameters) {
                values = { ...parameters };
            }

            delete values[key];
            values[key.replace(/[%{} ]/g, '').trim()] = parameters[key];
        }
    }

    return intlMessage.format(values) as string;
}
