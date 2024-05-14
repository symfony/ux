import {strtr} from '../utils';

/**
 * This code is adapted from the Symfony Translator Trait (6.2)
 * @see https://github.com/symfony/symfony/blob/015d5015e353ee5448bf7c350de0db4a03f8e13a/src/Symfony/Contracts/Translation/TranslatorTrait.php
 */

/**
 * Translates the given message.
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
 * @private
 *
 * @param id         The message id
 * @param parameters An array of parameters for the message
 * @param locale     The locale
 */
export function format(id: string, parameters: Record<string, string | number>, locale: string): string {
    if (null === id || '' === id) {
        return '';
    }

    if (typeof parameters['%count%'] === 'undefined' || Number.isNaN(parameters['%count%'])) {
        return strtr(id, parameters);
    }

    const number = Number(parameters['%count%']);

    let parts: Array<string> = [];
    if (/^\|+$/.test(id)) {
        parts = id.split('|');
    } else {
        parts = id.match(/(?:\|\||[^|])+/g) || [];
    }

    const intervalRegex = /^(?<interval>({\s*(-?\d+(\.\d+)?[\s*,\s*\-?\d+(.\d+)?]*)\s*})|(?<left_delimiter>[[\]])\s*(?<left>-Inf|-?\d+(\.\d+)?)\s*,\s*(?<right>\+?Inf|-?\d+(\.\d+)?)\s*(?<right_delimiter>[[\]]))\s*(?<message>.*?)$/s;

    const standardRules: Array<string> = [];
    for (let part of parts) {
        part = part.trim().replace(/\|\|/g, '|');

        const matches = part.match(intervalRegex);
        if (matches) {
            /**
             * @type {interval: string, left_delimiter: string, left: string, right_delimiter: string, right: string, message: string}
             */
            const matchGroups: { [p: string]: string } = matches.groups || {};
            if (matches[2]) {
                for (const n of matches[3].split(',')) {
                    if (number === Number(n)) {
                        return strtr(matchGroups.message, parameters);
                    }
                }
            } else {
                const leftNumber = '-Inf' === matchGroups.left ? Number.NEGATIVE_INFINITY : Number(matchGroups.left);
                const rightNumber = ['Inf', '+Inf'].includes(matchGroups.right) ? Number.POSITIVE_INFINITY : Number(matchGroups.right);

                if (('[' === matchGroups.left_delimiter ? number >= leftNumber : number > leftNumber)
                    && (']' === matchGroups.right_delimiter ? number <= rightNumber : number < rightNumber)
                ) {
                    return strtr(matchGroups.message, parameters);
                }
            }
        } else {
            const ruleMatch = part.match(/^\w+:\s*(.*?)$/);
            standardRules.push(ruleMatch ? ruleMatch[1] : part);
        }
    }

    const position = getPluralizationRule(number, locale);
    if (typeof standardRules[position] === 'undefined') {
        // when there's exactly one rule given, and that rule is a standard
        // rule, use this rule
        if (1 === parts.length && typeof standardRules[0] !== 'undefined') {
            return strtr(standardRules[0], parameters);
        }

        throw new Error(`Unable to choose a translation for "${id}" with locale "${locale}" for value "${number}". Double check that this translation has the correct plural options (e.g. "There is one apple|There are %count% apples").`)
    }

    return strtr(standardRules[position], parameters);
}

/**
 * Returns the plural position to use for the given locale and number.
 *
 * The plural rules are derived from code of the Zend Framework (2010-09-25),
 * which is subject to the new BSD license (http://framework.zend.com/license/new-bsd).
 * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 */
function getPluralizationRule(number: number, locale: string): number {
    number = Math.abs(number);
    let _locale = locale;

    if (locale === 'pt_BR' || locale === 'en_US_POSIX') {
        return 0;
    }

    _locale = _locale.length > 3 ? _locale.substring(0, _locale.indexOf('_')) : _locale;

    switch (_locale) {
        case 'af':
        case 'bn':
        case 'bg':
        case 'ca':
        case 'da':
        case 'de':
        case 'el':
        case 'en':
        case 'en_US_POSIX':
        case 'eo':
        case 'es':
        case 'et':
        case 'eu':
        case 'fa':
        case 'fi':
        case 'fo':
        case 'fur':
        case 'fy':
        case 'gl':
        case 'gu':
        case 'ha':
        case 'he':
        case 'hu':
        case 'is':
        case 'it':
        case 'ku':
        case 'lb':
        case 'ml':
        case 'mn':
        case 'mr':
        case 'nah':
        case 'nb':
        case 'ne':
        case 'nl':
        case 'nn':
        case 'no':
        case 'oc':
        case 'om':
        case 'or':
        case 'pa':
        case 'pap':
        case 'ps':
        case 'pt':
        case 'so':
        case 'sq':
        case 'sv':
        case 'sw':
        case 'ta':
        case 'te':
        case 'tk':
        case 'ur':
        case 'zu':
            return (1 === number) ? 0 : 1;
        case 'am':
        case 'bh':
        case 'fil':
        case 'fr':
        case 'gun':
        case 'hi':
        case 'hy':
        case 'ln':
        case 'mg':
        case 'nso':
        case 'pt_BR':
        case 'ti':
        case 'wa':
            return (number < 2) ? 0 : 1;
        case 'be':
        case 'bs':
        case 'hr':
        case 'ru':
        case 'sh':
        case 'sr':
        case 'uk':
            return ((1 === number % 10) && (11 !== number % 100)) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);
        case 'cs':
        case 'sk':
            return (1 === number) ? 0 : (((number >= 2) && (number <= 4)) ? 1 : 2);
        case 'ga':
            return (1 === number) ? 0 : ((2 === number) ? 1 : 2);
        case 'lt':
            return ((1 === number % 10) && (11 !== number % 100)) ? 0 : (((number % 10 >= 2) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);
        case 'sl':
            return (1 === number % 100) ? 0 : ((2 === number % 100) ? 1 : (((3 === number % 100) || (4 === number % 100)) ? 2 : 3));
        case 'mk':
            return (1 === number % 10) ? 0 : 1;
        case 'mt':
            return (1 === number) ? 0 : (((0 === number) || ((number % 100 > 1) && (number % 100 < 11))) ? 1 : (((number % 100 > 10) && (number % 100 < 20)) ? 2 : 3));
        case 'lv':
            return (0 === number) ? 0 : (((1 === number % 10) && (11 !== number % 100)) ? 1 : 2);
        case 'pl':
            return (1 === number) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 12) || (number % 100 > 14))) ? 1 : 2);
        case 'cy':
            return (1 === number) ? 0 : ((2 === number) ? 1 : (((8 === number) || (11 === number)) ? 2 : 3));
        case 'ro':
            return (1 === number) ? 0 : (((0 === number) || ((number % 100 > 0) && (number % 100 < 20))) ? 1 : 2);
        case 'ar':
            return (0 === number) ? 0 : ((1 === number) ? 1 : ((2 === number) ? 2 : (((number % 100 >= 3) && (number % 100 <= 10)) ? 3 : (((number % 100 >= 11) && (number % 100 <= 99)) ? 4 : 5))));
        default:
            return 0
    }
}
