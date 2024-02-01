import { IntlMessageFormat } from 'intl-messageformat';

/**
 * @private
 *
 * @param id         The message id
 * @param parameters An array of parameters for the message
 * @param locale     The locale
 */
export function formatIntl(id: string, parameters: Record<string, string | number> = {}, locale: string): string {
    if (id === '') {
        return '';
    }

    const intlMessage = new IntlMessageFormat(id, [locale.replace('_', '-')], undefined, { ignoreTag: true });

    parameters = { ...parameters };

    Object.entries(parameters).forEach(([key, value]) => {
        if (key.includes('%') || key.includes('{')) {
            delete parameters[key];
            parameters[key.replace(/[%{} ]/g, '').trim()] = value;
        }
    });

    return intlMessage.format(parameters);
}
