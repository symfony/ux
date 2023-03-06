import ChangingItemsTracker from './ChangingItemsTracker';

/**
 * Tracks attribute changes for a specific element.
 */
export default class ElementChanges {
    private addedClasses: string[] = [];
    private removedClasses: string[] = [];

    private styleChanges: ChangingItemsTracker = new ChangingItemsTracker();
    private attributeChanges: ChangingItemsTracker = new ChangingItemsTracker();

    addClass(className: string) {
        if (this.removedClasses.includes(className)) {
            // this was previously removed, so we're just undoing that
            this.removedClasses = this.removedClasses.filter((name) => name !== className);

            return;
        }

        if (!this.addedClasses.includes(className)) {
            this.addedClasses.push(className);
        }
    }

    removeClass(className: string) {
        if (this.addedClasses.includes(className)) {
            // this was previously added, so we're just undoing that
            this.addedClasses = this.addedClasses.filter((name) => name !== className);

            return;
        }

        if (!this.removedClasses.includes(className)) {
            this.removedClasses.push(className);
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
        return this.addedClasses;
    }

    getRemovedClasses(): string[] {
        return this.removedClasses;
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
        this.addedClasses.forEach((className) => {
            element.classList.add(className);
        });

        this.removedClasses.forEach((className) => {
            element.classList.remove(className);
        });

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
            this.addedClasses.length === 0 &&
            this.removedClasses.length === 0 &&
            this.styleChanges.isEmpty() &&
            this.attributeChanges.isEmpty()
        );
    }
}
