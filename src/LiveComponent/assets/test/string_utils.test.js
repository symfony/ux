import { combineSpacedArray } from '../dist/string_utils';

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
