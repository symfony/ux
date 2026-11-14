import { describe, expect, it } from 'vitest';
import { getDeepData } from '../../src/data_manipulation_utils';

describe('getDeepData', () => {
    const dataset: Array<{ data: any; path: string; expected: any }> = [
        { data: { firstName: 'Ryan' }, path: 'firstName', expected: 'Ryan' },
        { data: { firstName: 'Ryan' }, path: 'lastName', expected: undefined },
        { data: {}, path: 'firstName', expected: undefined },
        { data: { user: { firstName: 'Ryan' } }, path: 'user.firstName', expected: 'Ryan' },
        { data: { user: { firstName: 'Ryan' } }, path: 'user.lastName', expected: undefined },
        { data: { user: { firstName: 'Ryan' } }, path: 'user', expected: { firstName: 'Ryan' } },
        { data: { user: { name: { first: 'Ryan' } } }, path: 'user.name.first', expected: 'Ryan' },
        { data: { user: {} }, path: 'user.name.first', expected: undefined },
        { data: { firstName: null }, path: 'firstName', expected: null },
        { data: { user: null }, path: 'user', expected: null },
        { data: { tags: ['a', 'b'] }, path: 'tags.1', expected: 'b' },
        { data: { tags: ['a', 'b'] }, path: 'tags.5', expected: undefined },
        { data: { user: 5 }, path: 'user.firstName', expected: undefined },
    ];

    dataset.forEach(({ data, path, expected }) => {
        it(`getDeepData(${JSON.stringify(data)}, "${path}") returns ${JSON.stringify(expected)}`, () => {
            expect(getDeepData(data, path)).toEqual(expected);
        });
    });

    it('throws when an intermediate segment is missing', () => {
        expect(() => getDeepData({}, 'user.name.first')).toThrow(TypeError);
    });
});
