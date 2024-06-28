import { IntlMessageFormat } from 'intl-messageformat';

function formatIntl(id, parameters = {}, locale) {
    if (id === '') {
        return '';
    }
    const intlMessage = new IntlMessageFormat(id, [locale.replace('_', '-')], undefined, { ignoreTag: true });
    parameters = Object.assign({}, parameters);
    Object.entries(parameters).forEach(([key, value]) => {
        if (key.includes('%') || key.includes('{')) {
            delete parameters[key];
            parameters[key.replace(/[%{} ]/g, '').trim()] = value;
        }
    });
    return intlMessage.format(parameters);
}

function strtr(string, replacePairs) {
    const regex = Object.entries(replacePairs).map(([from]) => {
        return from.replace(/([-[\]{}()*+?.\\^$|#,])/g, '\\$1');
    });
    if (regex.length === 0) {
        return string;
    }
    return string.replace(new RegExp(regex.join('|'), 'g'), (matched) => replacePairs[matched].toString());
}

function format(id, parameters = {}, locale) {
    if (null === id || '' === id) {
        return '';
    }
    if (typeof parameters['%count%'] === 'undefined' || Number.isNaN(parameters['%count%'])) {
        return strtr(id, parameters);
    }
    const number = Number(parameters['%count%']);
    let parts = [];
    if (/^\|+$/.test(id)) {
        parts = id.split('|');
    }
    else {
        parts = id.match(/(?:\|\||[^|])+/g) || [];
    }
    const intervalRegex = /^(?<interval>({\s*(-?\d+(\.\d+)?[\s*,\s*\-?\d+(.\d+)?]*)\s*})|(?<left_delimiter>[[\]])\s*(?<left>-Inf|-?\d+(\.\d+)?)\s*,\s*(?<right>\+?Inf|-?\d+(\.\d+)?)\s*(?<right_delimiter>[[\]]))\s*(?<message>.*?)$/s;
    const standardRules = [];
    for (let part of parts) {
        part = part.trim().replace(/\|\|/g, '|');
        const matches = part.match(intervalRegex);
        if (matches) {
            const matchGroups = matches.groups || {};
            if (matches[2]) {
                for (const n of matches[3].split(',')) {
                    if (number === Number(n)) {
                        return strtr(matchGroups.message, parameters);
                    }
                }
            }
            else {
                const leftNumber = '-Inf' === matchGroups.left ? Number.NEGATIVE_INFINITY : Number(matchGroups.left);
                const rightNumber = ['Inf', '+Inf'].includes(matchGroups.right) ? Number.POSITIVE_INFINITY : Number(matchGroups.right);
                if (('[' === matchGroups.left_delimiter ? number >= leftNumber : number > leftNumber)
                    && (']' === matchGroups.right_delimiter ? number <= rightNumber : number < rightNumber)) {
                    return strtr(matchGroups.message, parameters);
                }
            }
        }
        else {
            const ruleMatch = part.match(/^\w+:\s*(.*?)$/);
            standardRules.push(ruleMatch ? ruleMatch[1] : part);
        }
    }
    const position = getPluralizationRule(number, locale);
    if (typeof standardRules[position] === 'undefined') {
        if (1 === parts.length && typeof standardRules[0] !== 'undefined') {
            return strtr(standardRules[0], parameters);
        }
        throw new Error(`Unable to choose a translation for "${id}" with locale "${locale}" for value "${number}". Double check that this translation has the correct plural options (e.g. "There is one apple|There are %count% apples").`);
    }
    return strtr(standardRules[position], parameters);
}
function getPluralizationRule(number, locale) {
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
            return (1 == number) ? 0 : 1;
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
            return ((1 == number % 10) && (11 != number % 100)) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);
        case 'cs':
        case 'sk':
            return (1 == number) ? 0 : (((number >= 2) && (number <= 4)) ? 1 : 2);
        case 'ga':
            return (1 == number) ? 0 : ((2 == number) ? 1 : 2);
        case 'lt':
            return ((1 == number % 10) && (11 != number % 100)) ? 0 : (((number % 10 >= 2) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);
        case 'sl':
            return (1 == number % 100) ? 0 : ((2 == number % 100) ? 1 : (((3 == number % 100) || (4 == number % 100)) ? 2 : 3));
        case 'mk':
            return (1 == number % 10) ? 0 : 1;
        case 'mt':
            return (1 == number) ? 0 : (((0 == number) || ((number % 100 > 1) && (number % 100 < 11))) ? 1 : (((number % 100 > 10) && (number % 100 < 20)) ? 2 : 3));
        case 'lv':
            return (0 == number) ? 0 : (((1 == number % 10) && (11 != number % 100)) ? 1 : 2);
        case 'pl':
            return (1 == number) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 12) || (number % 100 > 14))) ? 1 : 2);
        case 'cy':
            return (1 == number) ? 0 : ((2 == number) ? 1 : (((8 == number) || (11 == number)) ? 2 : 3));
        case 'ro':
            return (1 == number) ? 0 : (((0 == number) || ((number % 100 > 0) && (number % 100 < 20))) ? 1 : 2);
        case 'ar':
            return (0 == number) ? 0 : ((1 == number) ? 1 : ((2 == number) ? 2 : (((number % 100 >= 3) && (number % 100 <= 10)) ? 3 : (((number % 100 >= 11) && (number % 100 <= 99)) ? 4 : 5))));
        default:
            return 0;
    }
}

let _locale = null;
let _localeFallbacks = {};
let _registeredTranslations = {};
function setLocale(locale) {
    _locale = locale;
}
function getLocale() {
    return (_locale ||
        document.documentElement.getAttribute('data-symfony-ux-translator-locale') ||
        document.documentElement.lang ||
        'en');
}
function setLocaleFallbacks(localeFallbacks) {
    _localeFallbacks = localeFallbacks;
}
function getLocaleFallbacks() {
    return _localeFallbacks;
}
function trans(message, parameters = {}, domain = 'messages', locale = null) {
    if (typeof domain === 'undefined') {
        domain = 'messages';
    }
    if (typeof locale === 'undefined' || null === locale) {
        locale = getLocale();
    }
    if (typeof message === 'string') {
        message = getRegisteredMessage(message, domain);
    }
    if (typeof message.translations === 'undefined') {
        return message.id;
    }
    const localesFallbacks = getLocaleFallbacks();
    const translationsIntl = message.translations[`${domain}+intl-icu`];
    if (typeof translationsIntl !== 'undefined') {
        while (typeof translationsIntl[locale] === 'undefined') {
            locale = localesFallbacks[locale];
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
            locale = localesFallbacks[locale];
            if (!locale) {
                break;
            }
        }
        if (locale) {
            return format(translations[locale], parameters, locale);
        }
    }
    return message.id;
}
function registerDomain(domainTranslations) {
    for (const [domainName, translationsByLocale] of Object.entries(domainTranslations)) {
        _registeredTranslations[domainName] = translationsByLocale;
    }
}
function getRegisteredMessage(key, domain) {
    var _a;
    var _b;
    let message = { id: key, translations: {} };
    for (const domainName of [domain, domain + '+intl-icu']) {
        if (typeof _registeredTranslations[domainName] === 'undefined') {
            continue;
        }
        for (const [locale, translations] of Object.entries(_registeredTranslations[domainName])) {
            if (typeof translations[key] !== 'undefined') {
                (_a = (_b = message.translations)[domainName]) !== null && _a !== void 0 ? _a : (_b[domainName] = {});
                message.translations[domainName][locale] = translations[key];
            }
        }
    }
    return message;
}

export { getLocale, getLocaleFallbacks, registerDomain, setLocale, setLocaleFallbacks, trans };
