import { Controller } from '@hotwired/stimulus';

const DEFAULT_ITEMS_SELECTOR = ':scope > :is(div, fieldset)';

interface CollectionDataset extends DOMStringMap {
    prototype: string;
    currentIndex: string;
    itemsSelector?: string;
    addButtonTemplateId?: string;
    disableAddButton?: string;
    deleteButtonTemplateId?: string;
    disableDeleteButton?: string;
}

enum ButtonType {
    Add,
    Delete,
}

export default class extends Controller {
    static values = {
        addButtonTemplateId: '',
        disableAddButton: false,
        deleteButtonTemplateId: '',
        disableDeleteButton: false,
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

    createButton(collectionEl: HTMLElement, buttonType: ButtonType): HTMLElement {
        const attributeName = `${ButtonType[buttonType].toLowerCase()}ButtonTemplateId`;
        const buttonTemplateID = collectionEl.dataset[attributeName] ?? (this as any)[`${attributeName}Value`];
        if (buttonTemplateID && 'content' in document.createElement('template')) {
            // Get from template
            const buttonTemplate = document.getElementById(buttonTemplateID) as HTMLTemplateElement | null;
            if (!buttonTemplate) throw new Error(`template with ID "${buttonTemplateID}" not found`);

            const fragment = buttonTemplate.content.cloneNode(true) as DocumentFragment;
            if (1 !== fragment.children.length)
                throw new Error('template with ID "${buttonTemplateID}" must have exactly one child');

            return fragment.firstElementChild as HTMLElement;
        }

        // If no template is provided, create a raw HTML button
        const button = document.createElement('button') as HTMLButtonElement;
        button.type = 'button';
        button.textContent = buttonType === ButtonType.Add ? 'Add' : 'Delete';

        return button;
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
        addButton.onclick = (e) => {
            e.preventDefault();
            this.addItem(collectionEl);
        };
        collectionEl.appendChild(addButton);
    }

    addDeleteButton(collectionEl: HTMLElement, itemEl: HTMLElement) {
        const deleteButton = this.createButton(collectionEl, ButtonType.Delete);
        deleteButton.onclick = (e) => {
            e.preventDefault();
            itemEl.remove();
        };
        itemEl.appendChild(deleteButton);
    }
}
