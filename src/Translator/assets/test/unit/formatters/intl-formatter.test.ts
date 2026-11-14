/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { describe, expect, test } from 'vitest';
import { formatIntl } from '../../../src/formatters/intl-formatter';

describe('Intl Formatter', () => {
    test('format with named arguments', () => {
        const chooseMessage = `
{gender_of_host, select,
    female {{num_guests, plural, offset:1
        =0 {{host} does not give a party.}
        =1 {{host} invites {guest} to her party.}
        =2 {{host} invites {guest} and one other person to her party.}
        other {{host} invites {guest} as one of the # people invited to her party.}}}
    male {{num_guests, plural, offset:1
        =0 {{host} does not give a party.}
        =1 {{host} invites {guest} to his party.}
        =2 {{host} invites {guest} and one other person to his party.}
        other {{host} invites {guest} as one of the # people invited to his party.}}}
    other {{num_guests, plural, offset:1
        =0 {{host} does not give a party.}
        =1 {{host} invites {guest} to their party.}
        =2 {{host} invites {guest} and one other person to their party.}
        other {{host} invites {guest} as one of the # people invited to their party.}}}}`.trim();

        const message = formatIntl(
            chooseMessage,
            {
                gender_of_host: 'male',
                num_guests: 10,
                host: 'Fabien',
                guest: 'Guilherme',
            },
            'en'
        );

        expect(message).toEqual('Fabien invites Guilherme as one of the 9 people invited to his party.');
    });

    test('percents and brackets are trimmed', () => {
        expect(formatIntl('Hello {name}', { name: 'Fab' }, 'en')).toEqual('Hello Fab');
        expect(formatIntl('Hello {name}', { '%name%': 'Fab' }, 'en')).toEqual('Hello Fab');
        expect(formatIntl('Hello {name}', { '{{ name }}': 'Fab' }, 'en')).toEqual('Hello Fab');

        // Parameters object should not be modified
        const parameters = { '%name%': 'Fab' };
        expect(formatIntl('Hello {name}', parameters, 'en')).toEqual('Hello Fab');
        expect(parameters).toEqual({ '%name%': 'Fab' });
    });

    test('a renamed parameter wins over an already clean one', () => {
        expect(formatIntl('Hello {name}', { '%name%': 'a', name: 'b' }, 'en')).toEqual('Hello a');
        expect(formatIntl('Hello {name}', { name: 'b', '%name%': 'a' }, 'en')).toEqual('Hello a');
    });

    test('format with HTML inside', () => {
        expect(formatIntl('Hello <b>{name}</b>', { name: 'Fab' }, 'en')).toEqual('Hello <b>Fab</b>');
        expect(formatIntl('Hello {name}', { name: '<b>Fab</b>' }, 'en')).toEqual('Hello <b>Fab</b>');
    });

    test('format with locale containing underscore', () => {
        expect(formatIntl('Hello {name}', { name: 'Fab' }, 'en_US')).toEqual('Hello Fab');
        expect(formatIntl('Bonjour {name}', { name: 'Fab' }, 'fr_FR')).toEqual('Bonjour Fab');
    });

    test('format with a locale with script and region subcodes', () => {
        expect(formatIntl('{count, plural, other {# 則留言}}', { count: 2 }, 'zh_Hant_TW')).toEqual('2 則留言');
    });

    test('the same message formats per locale', () => {
        const plural = '{count, plural, one {# élément} other {# éléments}}';

        expect(formatIntl(plural, { count: 1 }, 'fr')).toEqual('1 élément');
        expect(formatIntl(plural, { count: 0 }, 'fr')).toEqual('0 élément');
        expect(formatIntl(plural, { count: 0 }, 'en')).toEqual('0 éléments');
        expect(formatIntl(plural, { count: 1 }, 'en')).toEqual('1 élément');

        expect(formatIntl('{n, number}', { n: 1234.5 }, 'fr')).toEqual('1 234,5');
        expect(formatIntl('{n, number}', { n: 1234.5 }, 'en')).toEqual('1,234.5');
    });

    test('repeated calls on the same message are stable', () => {
        for (let i = 0; i < 3; ++i) {
            expect(formatIntl('Hello {name}', { name: 'Fab' }, 'en')).toEqual('Hello Fab');
            expect(formatIntl('Hello {name}', { name: 'Hugo' }, 'en')).toEqual('Hello Hugo');
        }
    });

    test('an invalid message throws on every call', () => {
        expect(() => formatIntl('{count, plural, one {#}', { count: 1 }, 'en')).toThrow();
        expect(() => formatIntl('{count, plural, one {#}', { count: 1 }, 'en')).toThrow();
    });
});
