import ChangingItemsTracker from './ChangingItemsTracker';

/**
 * Tracks attribute changes for a specific element.
 */
export default class ElementChanges {
    private addedClasses: Set<string> = new Set();
    private removedClasses: Set<string> = new Set();

    private styleChanges: ChangingItemsTracker = new ChangingItemsTracker();
    private attributeChanges: ChangingItemsTracker = new ChangingItemsTracker();

    addClass(className: string) {
        if (!this.removedClasses.delete(className)) {
            this.addedClasses.add(className);
        }
    }

    removeClass(className: string) {
        if (!this.addedClasses.delete(className)) {
            this.removedClasses.add(className);
        }
    }

    addStyle(styleName: string, newValue: string, originalValue: string|null) {
        this.styleChanges.setItem(styleName, newValue, originalValue);
    }

    removeStyle(styleName: string, originalValue: string) {
        this.styleChanges.removeItem(styleName, originalValue);
    }

    addAttribute(attributeName: string, newValue: string, originalValue: string|null) {
        this.attributeChanges.setItem(attributeName, newValue, originalValue);
    }

    removeAttribute(attributeName: string, originalValue: string|null) {
        this.attributeChanges.removeItem(attributeName, originalValue);
    }

    getAddedClasses(): string[] {
        return [...this.addedClasses];
    }

    getRemovedClasses(): string[] {
        return [...this.removedClasses];
    }

    getChangedStyles(): { name: string, value: string }[] {
        return this.styleChanges.getChangedItems();
    }

    getRemovedStyles(): string[] {
        return this.styleChanges.getRemovedItems();
    }

    getChangedAttributes(): { name: string, value: string }[] {
        return this.attributeChanges.getChangedItems();
    }

    getRemovedAttributes(): string[] {
        return this.attributeChanges.getRemovedItems();
    }

    applyToElement(element: HTMLElement): void {
        element.classList.add(...this.addedClasses);
        element.classList.remove(...this.removedClasses);

        this.styleChanges.getChangedItems().forEach((change) => {
            element.style.setProperty(change.name, change.value);
            return;
        });

        this.styleChanges.getRemovedItems().forEach((styleName) => {
            element.style.removeProperty(styleName);
        });

        this.attributeChanges.getChangedItems().forEach((change) => {
            element.setAttribute(change.name, change.value);
        });

        this.attributeChanges.getRemovedItems().forEach((attributeName) => {
            element.removeAttribute(attributeName);
        });
    }

    isEmpty(): boolean {
        return (
            this.addedClasses.size === 0 &&
            this.removedClasses.size === 0 &&
            this.styleChanges.isEmpty() &&
            this.attributeChanges.isEmpty()
        );
    }
}
