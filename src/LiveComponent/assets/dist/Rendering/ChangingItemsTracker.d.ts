export default class {
    private changedItems;
    private removedItems;
    setItem(itemName: string, newValue: string, previousValue: string | null): void;
    removeItem(itemName: string, currentValue: string | null): void;
    getChangedItems(): {
        name: string;
        value: string;
    }[];
    getRemovedItems(): string[];
    isEmpty(): boolean;
}
