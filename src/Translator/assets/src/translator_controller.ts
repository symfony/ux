/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { format } from './formatters/formatter';
import { formatIntl } from './formatters/intl-formatter';
import type { DomainsOf, LocaleOf, LocaleType, MessageId, Messages, ParametersOf, RemoveIntlIcuSuffix } from './types';

export type * from './types.d';

export function getDefaultLocale(): LocaleType {
    return (
        document.documentElement.getAttribute('data-symfony-ux-translator-locale') || // <html data-symfony-ux-translator-locale="en_US">
        (document.documentElement.lang ? document.documentElement.lang.replace('-', '_') : null) || // <html lang="en-US">
        'en'
    );
}

export function createTranslator<TMessages extends Messages>({
    messages,
    locale = getDefaultLocale(),
    localeFallbacks = {},
    throwWhenNotFound = false,
}: {
    messages: TMessages;
    locale?: LocaleType;
    localeFallbacks?: Record<LocaleType, LocaleType>;
    throwWhenNotFound?: boolean;
}): {
    setLocale(locale: LocaleType): void;
    getLocale(): LocaleType;
    setThrowWhenNotFound(throwWhenNotFound: boolean): void;
    trans<
        TMessageId extends keyof TMessages & MessageId,
        TMessage extends TMessages[TMessageId],
        TDomain extends DomainsOf<TMessage>,
        TParameters extends ParametersOf<TMessage, TDomain>,
    >(
        id: TMessageId,
        parameters?: TParameters,
        domain?: RemoveIntlIcuSuffix<TDomain> | undefined,
        locale?: LocaleOf<TMessage>
    ): string;
} {
    const _messages = messages;
    const _localeFallbacks = localeFallbacks;
    let _locale = locale;
    let _throwWhenNotFound = throwWhenNotFound;

    /**
     * Sets the locale.
     */
    function setLocale(locale: LocaleType) {
        _locale = locale;
    }

    /**
     * Returns the current locale.
     */
    function getLocale(): LocaleType {
        return _locale;
    }

    /**
     * Sets whether an error should be thrown when a translation is not found.
     */
    function setThrowWhenNotFound(throwWhenNotFound: boolean) {
        _throwWhenNotFound = throwWhenNotFound;
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
     * @param id         The message ID
     * @param parameters An array of parameters for the message
     * @param domain     The domain for the message or null to use the default
     * @param locale     The locale or null to use the default
     */
    function trans<
        TMessageId extends keyof TMessages & MessageId,
        TMessage extends TMessages[TMessageId],
        TDomain extends DomainsOf<TMessage>,
        TParameters extends ParametersOf<TMessage, TDomain>,
    >(
        id: TMessageId,
        parameters: TParameters = {} as TParameters,
        domain: RemoveIntlIcuSuffix<TDomain> | undefined = 'messages' as RemoveIntlIcuSuffix<TDomain>,
        locale: LocaleOf<TMessage> | null = null
    ): string {
        if (typeof domain === 'undefined') {
            domain = 'messages' as RemoveIntlIcuSuffix<TDomain>;
        }

        if (typeof locale === 'undefined' || null === locale) {
            locale = _locale as LocaleOf<TMessage>;
        }

        const message = _messages[id] ?? (null as TMessage | null);
        if (message === null) {
            return id;
        }

        const translationsIntl = message.translations[`${domain}+intl-icu`] ?? undefined;
        if (typeof translationsIntl !== 'undefined') {
            while (typeof translationsIntl[locale] === 'undefined') {
                locale = _localeFallbacks[locale] as LocaleOf<TMessage>;
                if (!locale) {
                    break;
                }
            }

            if (locale) {
                return formatIntl(translationsIntl[locale], parameters, locale);
            }
        }

        const translations = message.translations[domain] ?? undefined;
        if (typeof translations !== 'undefined') {
            while (typeof translations[locale] === 'undefined') {
                locale = _localeFallbacks[locale] as LocaleOf<TMessage>;
                if (!locale) {
                    break;
                }
            }

            if (locale) {
                return format(translations[locale], parameters, locale);
            }
        }

        if (_throwWhenNotFound) {
            throw new Error(`No translation message found with id "${id}".`);
        }

        return id;
    }

    return {
        setLocale,
        getLocale,
        setThrowWhenNotFound,
        trans,
    };
}
