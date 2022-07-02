import { Controller } from '@hotwired/stimulus';

const DEFAULT_ITEMS_SELECTOR = ':scope > :is(div, fieldset)';
var ButtonType;
(function (ButtonType) {
    ButtonType["Add"] = "add";
    ButtonType["Delete"] = "delete";
})(ButtonType || (ButtonType = {}));
class controller extends Controller {
    connect() {
        this.connectCollection(this.element);
    }
    connectCollection(parent) {
        parent.querySelectorAll('[data-prototype]').forEach((el) => {
            const collectionEl = el;
            const items = this.getItems(collectionEl);
            collectionEl.dataset.currentIndex = items.length.toString();
            this.addAddButton(collectionEl);
            this.getItems(collectionEl).forEach(itemEl => this.addDeleteButton(collectionEl, itemEl));
        });
    }
    getItems(collectionElement) {
        return collectionElement.querySelectorAll(collectionElement.dataset.itemsSelector || DEFAULT_ITEMS_SELECTOR);
    }
    createButton(collectionEl, buttonType) {
        const buttonTemplateID = collectionEl.dataset[`${buttonType}ButtonTemplateId`];
        if (buttonTemplateID && 'content' in document.createElement('template')) {
            const buttonTemplate = document.getElementById(buttonTemplateID);
            if (!buttonTemplate)
                throw new Error(`element with ID "${buttonTemplateID}" not found`);
            return buttonTemplate.content.cloneNode(true);
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = buttonType === ButtonType.Add ? 'Add' : 'Delete';
        return button;
    }
    addItem(collectionEl) {
        const currentIndex = collectionEl.dataset.currentIndex;
        collectionEl.dataset.currentIndex++;
        const collectionNamePattern = collectionEl.id.replace(/_/g, '(?:_|\\[|]\\[)');
        const prototype = collectionEl.dataset.prototype
            .replace('__name__label__', currentIndex)
            .replace(new RegExp(`(${collectionNamePattern}(?:_|]\\[))__name__`, 'g'), `$1${currentIndex}`);
        const fakeEl = document.createElement('div');
        fakeEl.innerHTML = prototype;
        const itemEl = fakeEl.firstElementChild;
        this.connectCollection(itemEl);
        this.addDeleteButton(collectionEl, itemEl);
        const items = this.getItems(collectionEl);
        items.length ? items[items.length - 1].insertAdjacentElement('afterend', itemEl) : collectionEl.prepend(itemEl);
    }
    addAddButton(collectionEl) {
        const addButton = this.createButton(collectionEl, ButtonType.Add);
        addButton.onclick = (e) => {
            e.preventDefault();
            this.addItem(collectionEl);
        };
        collectionEl.appendChild(addButton);
    }
    addDeleteButton(collectionEl, itemEl) {
        const deleteButton = this.createButton(collectionEl, ButtonType.Delete);
        deleteButton.onclick = (e) => {
            e.preventDefault();
            itemEl.remove();
        };
        itemEl.appendChild(deleteButton);
    }
}

export { controller as default };
