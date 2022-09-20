import { Controller } from '@hotwired/stimulus';

const DEFAULT_ITEMS_SELECTOR = ':scope > :is(div, fieldset)';
var ButtonType;
(function (ButtonType) {
    ButtonType[ButtonType["Add"] = 0] = "Add";
    ButtonType[ButtonType["Delete"] = 1] = "Delete";
})(ButtonType || (ButtonType = {}));
class default_1 extends Controller {
    connect() {
        this.connectCollection(this.element);
    }
    connectCollection(parent) {
        parent.querySelectorAll('[data-prototype]').forEach((el) => {
            const collectionEl = el;
            const items = this.getItems(collectionEl);
            collectionEl.dataset.currentIndex = items.length.toString();
            this.addAddButton(collectionEl);
            this.getItems(collectionEl).forEach((itemEl) => this.addDeleteButton(collectionEl, itemEl));
        });
    }
    getItems(collectionElement) {
        return collectionElement.querySelectorAll(collectionElement.dataset.itemsSelector || DEFAULT_ITEMS_SELECTOR);
    }
    createButton(collectionEl, buttonType) {
        var _a;
        const attributeName = `${ButtonType[buttonType].toLowerCase()}Button`;
        const button = (_a = collectionEl.dataset[attributeName]) !== null && _a !== void 0 ? _a : this.element.dataset[attributeName];
        console.log(button);
        if ('' === button)
            return null;
        if (undefined === button || !('content' in document.createElement('template'))) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = ButtonType[buttonType];
            return button;
        }
        const buttonTemplate = document.getElementById(button);
        if (!buttonTemplate)
            throw new Error(`template with ID "${buttonTemplate}" not found`);
        const fragment = buttonTemplate.content.cloneNode(true);
        if (1 !== fragment.children.length)
            throw new Error('template with ID "${buttonTemplateID}" must have exactly one child');
        return fragment.firstElementChild;
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
        if (!addButton)
            return;
        addButton.onclick = (e) => {
            e.preventDefault();
            this.addItem(collectionEl);
        };
        collectionEl.appendChild(addButton);
    }
    addDeleteButton(collectionEl, itemEl) {
        const deleteButton = this.createButton(collectionEl, ButtonType.Delete);
        if (!deleteButton)
            return;
        deleteButton.onclick = (e) => {
            e.preventDefault();
            itemEl.remove();
        };
        itemEl.appendChild(deleteButton);
    }
}
default_1.values = {
    addButton: '',
    deleteButton: '',
};

export { default_1 as default };
