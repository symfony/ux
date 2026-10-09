import { describe, expect, it } from 'vitest';
import {
    decodeQueryOrFragment,
    encodePath,
    httpBuildQuery,
    isPhpTruthy,
    looseEquals,
    rawUrlEncode,
    toPhpString,
} from '../../src/php';

describe('toPhpString', () => {
    it.each([
        [null, ''],
        [undefined, ''],
        [false, ''],
        [true, '1'],
        [0, '0'],
        [12.5, '12.5'],
        ['abc', 'abc'],
    ])('casts %s', (value, expected) => {
        expect(toPhpString(value)).toBe(expected);
    });
});

describe('isPhpTruthy', () => {
    it.each([
        [null, false],
        ['', false],
        ['0', false],
        [0, false],
        [false, false],
        ['fr', true],
        [1, true],
    ])('%s', (value, expected) => {
        expect(isPhpTruthy(value)).toBe(expected);
    });
});

describe('rawUrlEncode', () => {
    it('encodes like PHP rawurlencode()', () => {
        expect(rawUrlEncode("a b!'()*~-_.é/")).toBe('a%20b%21%27%28%29%2A~-_.%C3%A9%2F');
    });
});

describe('encodePath', () => {
    it('keeps the chars UrlGenerator decodes in paths', () => {
        expect(encodePath('/a b/@:;,=+!*|%2F')).toBe('/a%20b/@:;,=+!*|%2F');
    });
});

describe('decodeQueryOrFragment', () => {
    it('decodes the chars UrlGenerator allows in queries and fragments', () => {
        expect(decodeQueryOrFragment('%2F%3F%40%3A%21%3B%2C%2A%26%3D%2B%23%252F')).toBe('/?@:!;,*%26%3D%2B%23%2F');
    });
});

describe('httpBuildQuery', () => {
    it('builds nested queries like PHP http_build_query() with RFC 3986', () => {
        expect(
            httpBuildQuery({
                a: 'b c',
                list: ['x', 'y'],
                map: { k: 'v' },
                yes: true,
                no: false,
                skipped: null,
                empty: [],
            })
        ).toBe('a=b%20c&list%5B0%5D=x&list%5B1%5D=y&map%5Bk%5D=v&yes=1&no=0');
    });

    it('casts non-plain objects to strings', () => {
        class Stringable {
            toString(): string {
                return 'x';
            }
        }

        expect(httpBuildQuery({ value: new Stringable() })).toBe('value=x');
    });
});

describe('looseEquals', () => {
    it.each([
        ['1', 1, true],
        ['1.0', 1, true],
        ['abc', 'abc', true],
        ['abc', 'abd', false],
        [null, '', true],
        [null, '0', false],
        [true, 'fr', true],
        [false, '', true],
    ])('%s == %s', (a, b, expected) => {
        expect(looseEquals(a, b)).toBe(expected);
    });
});
