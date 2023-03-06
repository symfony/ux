import ChangingItemsTracker from '../../src/Rendering/ChangingItemsTracker';

describe('ChangingItemsTracker', () => {
    it('tracks style change red -> blue -> green', async () => {
        // red -> blue -> green
        const items = new ChangingItemsTracker();
        items.setItem('color', 'blue', 'red');
        items.setItem('color', 'green', 'blue');
        expect(items.getChangedItems()).toHaveLength(1);
        expect(items.getChangedItems()[0]).toEqual({ name: 'color', value: 'green'});
        expect(items.getRemovedItems()).toHaveLength(0);
    });

    it('tracks style additions', async () => {
        const items = new ChangingItemsTracker();
        items.setItem('color', 'blue', null);
        items.setItem('color', 'green', 'blue');
        expect(items.getChangedItems()).toHaveLength(1);
        expect(items.getChangedItems()[0]).toEqual({ name: 'color', value: 'green'});
        expect(items.getRemovedItems()).toHaveLength(0);
    });

    it('tracks style additions then removals', async () => {
        const items = new ChangingItemsTracker();
        items.setItem('color', 'blue', null);
        items.setItem('color', 'green', 'blue');
        items.removeItem('color', 'green');
        expect(items.getChangedItems()).toHaveLength(0);
        // the item was NOT originally present (shown by its "null" as the
        // previous value in the first setItem() call), so it should not be
        // included in the list of removed items
        expect(items.getRemovedItems()).toHaveLength(0);
    });

    it('tracks style change red -> blue -> red', async () => {
        const changes = new ChangingItemsTracker();
        changes.setItem('color', 'blue', 'red');
        changes.setItem('color', 'red', 'blue');
        expect(changes.getChangedItems()).toHaveLength(0);
        expect(changes.getRemovedItems()).toHaveLength(0);
    });

    it('tracks style change red -> blue -> REMOVED', async () => {
        const changes = new ChangingItemsTracker();
        changes.setItem('color', 'blue', 'red');
        changes.removeItem('color', 'blue');
        expect(changes.getChangedItems()).toHaveLength(0);
        expect(changes.getRemovedItems()).toHaveLength(1);
        expect(changes.getRemovedItems()).toContain('color');
    });

    it('tracks style removal', async () => {
        const changes = new ChangingItemsTracker();
        changes.removeItem('color', 'red');
        expect(changes.getChangedItems()).toHaveLength(0);
        expect(changes.getRemovedItems()).toHaveLength(1);
        expect(changes.getRemovedItems()).toContain('color');
    });

    it('tracks removal and setting back to original value: red -> blue -> REMOVED -> red', async () => {
        const changes = new ChangingItemsTracker();
        changes.setItem('color', 'blue', 'red');
        changes.removeItem('color', 'blue');
        changes.setItem('color', 'red', null);
        expect(changes.getChangedItems()).toHaveLength(0);
        expect(changes.getRemovedItems()).toHaveLength(0);
    });

    it('tracks removal and setting back to original when attribute was already present', async () => {
        const changes = new ChangingItemsTracker();
        changes.removeItem('color', 'red');
        changes.setItem('color', 'red', null);
        expect(changes.getChangedItems()).toHaveLength(0);
        expect(changes.getRemovedItems()).toHaveLength(0);
    });

    it('tracks boolean attribute change', async () => {
        const changes = new ChangingItemsTracker();
        // both added and were not present before
        changes.setItem('checked', '', null);
        changes.setItem('required', '', null);
        changes.removeItem('required', '');
        expect(changes.getChangedItems()).toHaveLength(1);
        expect(changes.getChangedItems()).toContainEqual({ name: 'checked', value: '' });
        // this attribute was not originally present
        expect(changes.getRemovedItems()).toHaveLength(0);
    });
});
