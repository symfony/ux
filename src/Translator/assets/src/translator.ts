/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export type DomainType = string;
export type LocaleType = string;

export type TranslationsType = Record<DomainType, { parameters: ParametersType }>;
export type NoParametersType = Record<string, never>;
export type ParametersType = Record<string, string | number | Date> | NoParametersType;

export type RemoveIntlIcuSuffix<T> = T extends `${infer U}+intl-icu` ? U : T;
export type DomainsOf<M> = M extends Message<infer Translations, LocaleType> ? keyof Translations : never;
export type LocaleOf<M> = M extends Message<TranslationsType, infer Locale> ? Locale : never;
export type ParametersOf<M, D extends DomainType> = M extends Message<infer Translations, LocaleType>
    ? Translations[D] extends { parameters: infer Parameters }
        ? Parameters
        : never
    : never;

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export interface Message<Translations extends TranslationsType, Locale extends LocaleType> {
    id: string;
    translations: {
        [domain in DomainType]: {
            [locale in Locale]: string;
        };
    };
}

import { formatIntl } from './formatters/intl-formatter';
import { format } from './formatters/formatter';

let _locale: LocaleType | null = null;
let _localeFallbacks: Record<LocaleType, LocaleType> = {};
let _throwWhenNotFound = false;

export function setLocale(locale: LocaleType | null) {
    _locale = locale;
}

export function getLocale(): LocaleType {
    return (
        _locale ||
        document.documentElement.getAttribute('data-symfony-ux-translator-locale') || // <html data-symfony-ux-translator-locale="en">
        document.documentElement.lang || // <html lang="en">
        'en'
    );
}

export function throwWhenNotFound(enabled: boolean): void {
    _throwWhenNotFound = enabled;
}

export function setLocaleFallbacks(localeFallbacks: Record<LocaleType, LocaleType>): void {
    _localeFallbacks = localeFallbacks;
}

export function getLocaleFallbacks(): Record<LocaleType, LocaleType> {
    return _localeFallbacks;
}

/**
 * Translates the given message, in ICU format (see https://formatjs.io/docs/intl-messageformat) or Symfony format (see below).
 *
 * When a number is provided as a parameter named "%count%", the message is parsed for plural
 * forms and a translation is chosen according to this number using the following rules:
 *
 * Given a message with different plural translations separated by a
 * pipe (|), this method returns the correct portion of the message based
 * on the given number, locale and the pluralization rules in the message
 * itself.
 *
 * The message supports two different types of pluralization rules:
 *
 * interval: {0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples
 * indexed:  There is one apple|There are %count% apples
 *
 * The indexed solution can also contain labels (e.g. one: There is one apple).
 * This is purely for making the translations more clear - it does not
 * affect the functionality.
 *
 * The two methods can also be mixed:
 *     {0} There are no apples|one: There is one apple|more: There are %count% apples
 *
 * An interval can represent a finite set of numbers:
 *  {1,2,3,4}
 *
 * An interval can represent numbers between two numbers:
 *  [1, +Inf]
 *  ]-1,2[
 *
 * The left delimiter can be [ (inclusive) or ] (exclusive).
 * The right delimiter can be [ (exclusive) or ] (inclusive).
 * Beside numbers, you can use -Inf and +Inf for the infinite.
 *
 * @see https://en.wikipedia.org/wiki/ISO_31-11
 *
 * @param message    The message
 * @param parameters An array of parameters for the message
 * @param domain     The domain for the message or null to use the default
 * @param locale     The locale or null to use the default
 */
export function trans<
    M extends Message<TranslationsType, LocaleType>,
    D extends DomainsOf<M>,
    P extends ParametersOf<M, D>,
>(
    ...args: P extends NoParametersType
        ? [message: M, parameters?: P, domain?: RemoveIntlIcuSuffix<D>, locale?: LocaleOf<M>]
        : [message: M, parameters: P, domain?: RemoveIntlIcuSuffix<D>, locale?: LocaleOf<M>]
): string;
export function trans<
    M extends Message<TranslationsType, LocaleType>,
    D extends DomainsOf<M>,
    P extends ParametersOf<M, D>,
>(
    message: M,
    parameters: P = {} as P,
    domain: RemoveIntlIcuSuffix<DomainsOf<M>> | undefined = 'messages' as RemoveIntlIcuSuffix<DomainsOf<M>>,
    locale: LocaleOf<M> | null = null
): string {
    if (typeof domain === 'undefined') {
        domain = 'messages' as RemoveIntlIcuSuffix<DomainsOf<M>>;
    }

    if (typeof locale === 'undefined' || null === locale) {
        locale = getLocale() as LocaleOf<M>;
    }

    if (typeof message.translations === 'undefined') {
        return message.id;
    }

    const localesFallbacks = getLocaleFallbacks();

    const translationsIntl = message.translations[`${domain}+intl-icu`];
    if (typeof translationsIntl !== 'undefined') {
        while (typeof translationsIntl[locale] === 'undefined') {
            locale = localesFallbacks[locale] as LocaleOf<M>;
            if (!locale) {
                break;
            }
        }

        if (locale) {
            return formatIntl(translationsIntl[locale], parameters, locale);
        }
    }

    const translations = message.translations[domain];
    if (typeof translations !== 'undefined') {
        while (typeof translations[locale] === 'undefined') {
            locale = localesFallbacks[locale] as LocaleOf<M>;
            if (!locale) {
                break;
            }
        }

        if (locale) {
            return format(translations[locale], parameters, locale);
        }
    }

    if (_throwWhenNotFound) {
        throw new Error(`No translation message found with id "${message.id}".`);
    }

    return message.id;
}
