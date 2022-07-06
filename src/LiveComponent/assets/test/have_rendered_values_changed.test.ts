import { haveRenderedValuesChanged } from '../src/have_rendered_values_changed';

const runTest = (originalData: any, currentData: any, newData: any, expected: boolean) => {
    const result = haveRenderedValuesChanged(
        JSON.stringify(originalData),
        JSON.stringify(currentData),
        JSON.stringify(newData),
    );

    expect(result).toBe(expected);
};

describe('haveRenderedValuesChanged', () => {
    it('returns false if the "new" data matches the "original" data', () => {
        runTest(
            // original
            { value: 'original' },
            // current
            { value: 'child component did change' },
            // new
            { value: 'original' },
            false
        );
    });

    it('returns false if the "new" data matches the "current" data', () => {
        // in this case, the component is already aware of the data

        runTest(
            // original
            { value: 'original' },
            // current
            { value: 'updated' },
            // new
            { value: 'updated' },
            false
        );
    });

    it('returns false if the new data that *has* changed vs original is equal to current data', () => {
        // This is a combination of the first two cases - each key
        // represents one of the first two test situations.

        // we see that the "firstName" key has changed between the
        // new data vs the original. But, we also see that "firstName"
        // is equal between new & current, meaning the component is already
        // aware of this change.

        // the "lastName" key has changed between current and new, but we
        // don't care, because this hasn't changed since the original render

        runTest(
            // original
            { firstName: 'Beckett', lastName: 'Weaver' },
            // current
            { firstName: 'Ryan', lastName: 'Pelham' },
            // new
            { firstName: 'Ryan', lastName: 'Weaver' },
            false
        );
    });

    it('returns true correctly even with rearranged key', () => {
        // same as previous case, but keys rearranged

        runTest(
            // original
            { firstName: 'Beckett', lastName: 'Weaver', favoriteColor: 'orange' },
            // current
            { firstName: 'Beckett', favoriteColor: 'orange', lastName: 'Weaver' },
            // new
            { favoriteColor: 'orange', lastName: 'Weaver', firstName: 'Beckett' },
            false
        );
    });

    it('returns true if at least one key in the new data has changed since the original data *and* it is not equal to the current data', () => {
        // something truly changed in how the component is rendered that
        // the component isn't aware of yet

        runTest(
            // original
            { firstName: 'Beckett', lastName: 'Weaver', error: null },
            // current
            { firstName: 'Ryan', lastName: 'Pelham', error: null },
            // new
            { firstName: 'Ryan', lastName: 'Weaver', error: 'That sounds like a fake name' },
            true
        );
    });

    // todo - test with missing keys
});
