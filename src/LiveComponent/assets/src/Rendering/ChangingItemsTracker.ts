/**
 * Helps track added/changed styles or attributes.
 */
export default class {
    // e.g. a Map with key "color" & value { original: 'previousValue', new: 'newValue' },
    private changedItems: Map<string, { original: string | null; new: string }> = new Map();
    private removedItems: Map<string, { original: string | null }> = new Map();

    /**
     * A "null" previousValue means the item was NOT previously present.
     */
    setItem(itemName: string, newValue: string, previousValue: string | null): void {
        if (this.removedItems.has(itemName)) {
            // this was previously removed

            const removedRecord = this.removedItems.get(itemName) as { original: string };
            this.removedItems.delete(itemName);

            if (removedRecord.original === newValue) {
                // we just set the item to its original value
                return;
            }
        }

        if (this.changedItems.has(itemName)) {
            // this was previously changed
            const originalRecord = this.changedItems.get(itemName) as { original: string; new: string };
            if (originalRecord.original === newValue) {
                // it just reverted to its original value!
                this.changedItems.delete(itemName);

                return;
            }

            // keep the original value, but update the new value
            this.changedItems.set(itemName, { original: originalRecord.original, new: newValue });

            return;
        }

        this.changedItems.set(itemName, { original: previousValue, new: newValue });
    }

    removeItem(itemName: string, currentValue: string | null): void {
        let trueOriginalValue = currentValue;
        if (this.changedItems.has(itemName)) {
            // this was previously changed, so we're just undoing that
            const originalRecord = this.changedItems.get(itemName) as { original: string; new: string };
            trueOriginalValue = originalRecord.original;

            this.changedItems.delete(itemName);

            if (trueOriginalValue === null) {
                // this item was NOT originally present, so do not add it to changed items
                return;
            }
        }

        if (!this.removedItems.has(itemName)) {
            this.removedItems.set(itemName, { original: trueOriginalValue });
        }
    }

    getChangedItems(): { name: string; value: string }[] {
        return Array.from(this.changedItems, ([name, { new: value }]) => ({ name, value }));
    }

    getRemovedItems(): string[] {
        return Array.from(this.removedItems.keys());
    }

    isEmpty(): boolean {
        return this.changedItems.size === 0 && this.removedItems.size === 0;
    }
}
