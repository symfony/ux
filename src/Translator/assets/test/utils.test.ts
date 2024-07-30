import {strtr} from '../src/utils';

describe('Utils', () => {
    test.concurrent.each<[string, string, Record<string, string>]>([
        // https://github.com/php/php-src/blob/master/ext/standard/tests/strings/strtr_basic.phpt
        ['TEST STrTr', 'test strtr', {t: 'T', e: 'E', st: 'ST'}],
        ['TEST STrTr', 'test strtr', {t: 'T', e: 'E', st: 'ST'}],

        // https://github.com/php/php-src/blob/master/ext/standard/tests/strings/strtr_variation1.phpt
        ['a23', '123', {'1': 'a', a: '1', '2b3c': 'b2c3', b2c3: '3c2b'}],
        ['1bc', 'abc', {'1': 'a', a: '1', '2b3c': 'b2c3', b2c3: '3c2b'}],
        ['a1b2c3', '1a2b3c', {'1': 'a', a: '1', '2b3c': 'b2c3', b2c3: '3c2b'}],
        [`
a23
1bc
a1b2c3`, `
123
abc
1a2b3c`, {1: 'a', a: '1', '2b3c': 'b2c3', b2c3: '3c2b'}],

        // https://github.com/php/php-src/blob/master/ext/standard/tests/strings/strtr_variation2.phpt
        ['$', '%', {$: '%', '%': '$', '#*&@()': '()@&*#'}],
        ['#%*', '#$*', {$: '%', '%': '$', '#*&@()': '()@&*#'}],
        ['text & @()', 'text & @()', {$: '%', '%': '$', '#*&@()': '()@&*#'}],
        [`
$
#%*&
text & @()`, `
%
#$*&
text & @()`, {$: '%', '%': '$', '#*&@()': '()@&*#'}],
    ])('strtr', (expected, string, replacePairs) => {
        expect(strtr(string, replacePairs)).toEqual(expected);
    });
});