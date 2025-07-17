/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { formatIntl } from '../../src/formatters/intl-formatter';

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

    test('format with HTML inside', () => {
        expect(formatIntl('Hello <b>{name}</b>', { name: 'Fab' }, 'en')).toEqual('Hello <b>Fab</b>');
        expect(formatIntl('Hello {name}', { name: '<b>Fab</b>' }, 'en')).toEqual('Hello <b>Fab</b>');
    });

    test('format with locale containg underscore', () => {
        expect(formatIntl('Hello {name}', { name: 'Fab' }, 'en_US')).toEqual('Hello Fab');
        expect(formatIntl('Bonjour {name}', { name: 'Fab' }, 'fr_FR')).toEqual('Bonjour Fab');
    });
});
