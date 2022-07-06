import { combineSpacedArray, normalizeModelName } from '../src/string_utils';

describe('combinedSpacedArray', () => {
    it('parse normal array', () => {
        const items = combineSpacedArray(['hidden', 'other']);
        expect(items).toEqual(['hidden', 'other']);
    });

    it('parse array with spaced item', () => {
        const items = combineSpacedArray(['hidden other', 'bar']);
        expect(items).toEqual(['hidden', 'other', 'bar']);
    });
});

describe('normalizeModelName', () => {
    it('can normalize a boring string', () => {
        expect(normalizeModelName('firstName')).toEqual('firstName');
    });

    it('can normalize a string with []', () => {
        expect(normalizeModelName('user[firstName]')).toEqual('user.firstName');
    });

    it('can normalize a string ending in []', () => {
        expect(normalizeModelName('user[mailing][]')).toEqual('user.mailing');
    });
});
