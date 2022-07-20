import { Controller } from '@hotwired/stimulus';

const DEFAULT_ITEMS_SELECTOR = ':scope > :is(div, fieldset)';

interface CollectionDataset extends DOMStringMap {
    prototype: string;
    currentIndex: string;
    itemsSelector?: string;
    addButton?: string;
    deleteButton?: string;
}

enum ButtonType {
    Add,
    Delete,
}

export default class extends Controller {
    static values = {
        addButton: '',
        deleteButton: '',
    };

    connect() {
        this.connectCollection(this.element as HTMLElement);
    }

    connectCollection(parent: HTMLElement) {
        parent.querySelectorAll('[data-prototype]').forEach((el) => {
            const collectionEl = el as HTMLElement;
            const items = this.getItems(collectionEl);
            collectionEl.dataset.currentIndex = items.length.toString();

            this.addAddButton(collectionEl);

            this.getItems(collectionEl).forEach((itemEl) => this.addDeleteButton(collectionEl, itemEl as HTMLElement));
        });
    }

    getItems(collectionElement: HTMLElement) {
        return collectionElement.querySelectorAll(collectionElement.dataset.itemsSelector || DEFAULT_ITEMS_SELECTOR);
    }

    createButton(collectionEl: HTMLElement, buttonType: ButtonType): HTMLElement | null {
        const attributeName = `${ButtonType[buttonType].toLowerCase()}Button`;
        const button = collectionEl.dataset[attributeName] ?? (this.element as HTMLElement).dataset[attributeName];
        console.log(button);

        // Button explicitly disabled through data attribute
        if ('' === button) return null;

        // No data attribute provided or <template> not supported: create raw HTML button
        if (undefined === button || !('content' in document.createElement('template'))) {
            const button = document.createElement('button') as HTMLButtonElement;
            button.type = 'button';
            button.textContent = ButtonType[buttonType];

            return button;
        }

        // Use the template referenced by the data attribute
        const buttonTemplate = document.getElementById(button) as HTMLTemplateElement | null;
        if (!buttonTemplate) throw new Error(`template with ID "${buttonTemplate}" not found`);

        const fragment = buttonTemplate.content.cloneNode(true) as DocumentFragment;
        if (1 !== fragment.children.length)
            throw new Error('template with ID "${buttonTemplateID}" must have exactly one child');

        return fragment.firstElementChild as HTMLElement;
    }

    addItem(collectionEl: HTMLElement) {
        const currentIndex = (collectionEl.dataset as CollectionDataset).currentIndex;
        (collectionEl.dataset.currentIndex as unknown as number)++;

        const collectionNamePattern = collectionEl.id.replace(/_/g, '(?:_|\\[|]\\[)');

        const prototype = (collectionEl.dataset.prototype as string) // We're sure that dataset.prototype exists, because of the CSS selector used in connect()
            .replace('__name__label__', currentIndex)
            .replace(new RegExp(`(${collectionNamePattern}(?:_|]\\[))__name__`, 'g'), `$1${currentIndex}`);

        const fakeEl = document.createElement('div');
        fakeEl.innerHTML = prototype;
        const itemEl = fakeEl.firstElementChild as HTMLElement;

        this.connectCollection(itemEl);

        this.addDeleteButton(collectionEl, itemEl);

        const items = this.getItems(collectionEl);
        items.length ? items[items.length - 1].insertAdjacentElement('afterend', itemEl) : collectionEl.prepend(itemEl);
    }

    addAddButton(collectionEl: HTMLElement) {
        const addButton = this.createButton(collectionEl, ButtonType.Add);
        if (!addButton) return;

        addButton.onclick = (e) => {
            e.preventDefault();
            this.addItem(collectionEl);
        };
        collectionEl.appendChild(addButton);
    }

    addDeleteButton(collectionEl: HTMLElement, itemEl: HTMLElement) {
        const deleteButton = this.createButton(collectionEl, ButtonType.Delete);
        if (!deleteButton) return;

        deleteButton.onclick = (e) => {
            e.preventDefault();
            itemEl.remove();
        };
        itemEl.appendChild(deleteButton);
    }
}
