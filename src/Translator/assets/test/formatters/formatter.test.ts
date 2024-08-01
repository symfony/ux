/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { format } from '../../src/formatters/formatter';

describe('Formatter', () => {
    test.concurrent.each<[string, string, Record<string, string | number>]>([
        ['Symfony is great!', 'Symfony is great!', {}],
        ['Symfony is awesome!', 'Symfony is %what%!', { '%what%': 'awesome' }],
    ])('#%# format should returns %p', (expected, message, parameters) => {
        expect(format(message, parameters, 'en')).toEqual(expected);
    });

    test.concurrent.each<[string, string, number]>([
        ['There are no apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],
        ['There is one apple', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 1],
        ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 10],
        ['There are 0 apples', 'There is 1 apple|There are %count% apples', 0],
        ['There is 1 apple', 'There is 1 apple|There are %count% apples', 1],
        ['There are 10 apples', 'There is 1 apple|There are %count% apples', 10],
        // custom validation messages may be coded with a fixed value
        ['There are 2 apples', 'There are 2 apples', 2],
    ])('#%# format with choice should returns %p', (expected, message, number) => {
        expect(format(message, { '%count%': number }, 'en')).toEqual(expected);
    });

    test.concurrent.each<[string, number, string]>([
        ['foo', 3, '{1,2, 3 ,4}'],
        ['bar', 10, '{1,2, 3 ,4}'],
        ['bar', 3, '[1,2]'],
        ['foo', 1, '[1,2]'],
        ['foo', 2, '[1,2]'],
        ['bar', 1, ']1,2['],
        ['bar', 2, ']1,2['],
        ['foo', Math.log(0), '[-Inf,2['],
        ['foo', -Math.log(0), '[-2,+Inf]'],
    ])('#%# format interval should returns %p', (expected, number, interval) => {
        expect(format(`${interval} foo|[1,Inf[ bar`, { '%count%': number }, 'en')).toEqual(expected);
    });

    test.concurrent.each<[string, number]>([
        ['{0} There are no apples|{1} There is one apple', 2],
        ['{1} There is one apple|]1,Inf] There are %count% apples', 0],
        ['{1} There is one apple|]2,Inf] There are %count% apples', 2],
        ['{0} There are no apples|There is one apple', 2],
    ])('#%# test non matching message', (message, number) => {
        expect(() => format(message, { '%count%': number }, 'en')).toThrow(
            `Unable to choose a translation for "${message}" with locale "en" for value "${number}". Double check that this translation has the correct plural options (e.g. "There is one apple|There are %count% apples").`
        );
    });

    test.concurrent.each([
        ['There are no apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],
        [
            'There are no apples',
            '{0}     There are no apples|{1} There is one apple|]1,Inf] There are %count% apples',
            0,
        ],
        ['There are no apples', '{0}There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],

        ['There is one apple', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 1],

        ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 10],
        ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf]There are %count% apples', 10],
        [
            'There are 10 apples',
            '{0} There are no apples|{1} There is one apple|]1,Inf]     There are %count% apples',
            10,
        ],

        ['There are 0 apples', 'There is one apple|There are %count% apples', 0],
        ['There is one apple', 'There is one apple|There are %count% apples', 1],
        ['There are 10 apples', 'There is one apple|There are %count% apples', 10],

        ['There are 0 apples', 'one: There is one apple|more: There are %count% apples', 0],
        ['There is one apple', 'one: There is one apple|more: There are %count% apples', 1],
        ['There are 10 apples', 'one: There is one apple|more: There are %count% apples', 10],

        ['There are no apples', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 0],
        ['There is one apple', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 1],
        ['There are 10 apples', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 10],

        ['', '{0}|{1} There is one apple|]1,Inf] There are %count% apples', 0],
        ['', '{0} There are no apples|{1}|]1,Inf] There are %count% apples', 1],

        // Indexed only tests which are Gettext PoFile* compatible strings.
        ['There are 0 apples', 'There is one apple|There are %count% apples', 0],
        ['There is one apple', 'There is one apple|There are %count% apples', 1],
        ['There are 2 apples', 'There is one apple|There are %count% apples', 2],

        // Tests for float numbers
        [
            'There is almost one apple',
            '{0} There are no apples|]0,1[ There is almost one apple|{1} There is one apple|[1,Inf] There is more than one apple',
            0.7,
        ],
        [
            'There is one apple',
            '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple',
            1,
        ],
        [
            'There is more than one apple',
            '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple',
            1.7,
        ],
        [
            'There are no apples',
            '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple',
            0,
        ],
        [
            'There are no apples',
            '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple',
            0.0,
        ],
        [
            'There are no apples',
            '{0.0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple',
            0,
        ],

        // Test texts with new-lines
        // with double-quotes and \n in id & double-quotes and actual newlines in text
        [
            'This is a text with a\n            new-line in it. Selector = 0.',
            `{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.`,
            0,
        ],
        // with double-quotes and \n in id and single-quotes and actual newlines in text
        [
            'This is a text with a\n            new-line in it. Selector = 1.',
            `{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.`,
            1,
        ],
        [
            'This is a text with a\n            new-line in it. Selector > 1.',
            `{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.`,
            5,
        ],
        // with double-quotes and id split accros lines
        [
            `This is a text with a
            new-line in it. Selector = 1.`,
            `{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.`,
            1,
        ],
        // with single-quotes and id split accros lines
        [
            `This is a text with a
            new-line in it. Selector > 1.`,
            `{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.`,
            5,
        ],
        // with single-quotes and \n in text
        [
            'This is a text with a\nnew-line in it. Selector = 0.',
            '{0}This is a text with a\nnew-line in it. Selector = 0.|{1}This is a text with a\nnew-line in it. Selector = 1.|[1,Inf]This is a text with a\nnew-line in it. Selector > 1.',
            0,
        ],
        // with double-quotes and id split accros lines
        [
            'This is a text with a\nnew-line in it. Selector = 1.',
            '{0}This is a text with a\nnew-line in it. Selector = 0.|{1}This is a text with a\nnew-line in it. Selector = 1.|[1,Inf]This is a text with a\nnew-line in it. Selector > 1.',
            1,
        ],
        // esacape pipe
        [
            'This is a text with | in it. Selector = 0.',
            '{0}This is a text with || in it. Selector = 0.|{1}This is a text with || in it. Selector = 1.',
            0,
        ],
        // Empty plural set (2 plural forms) from a .PO file
        ['', '|', 1],
        // Empty plural set (3 plural forms) from a .PO file
        ['', '||', 1],

        // Floating values
        ['1.5 liters', '%count% liter|%count% liters', 1.5],
        ['1.5 litre', '%count% litre|%count% litres', 1.5, 'fr'],

        // Negative values
        ['-1 degree', '%count% degree|%count% degrees', -1],
        ['-1 degré', '%count% degré|%count% degrés', -1],
        ['-1.5 degrees', '%count% degree|%count% degrees', -1.5],
        ['-1.5 degré', '%count% degré|%count% degrés', -1.5, 'fr'],
        ['-2 degrees', '%count% degree|%count% degrees', -2],
        ['-2 degrés', '%count% degré|%count% degrés', -2],
    ])('#%# test choose', (expected, id, number, locale = 'en') => {
        expect(format(id, { '%count%': number }, locale)).toBe(expected);
    });
});
