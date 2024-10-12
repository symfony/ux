import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.index = 0;
        this.controllerName = 'collection';
    }
    connect() {
        this.controllerName = this.context.scope.identifier;
        this._dispatchEvent('collection:connect');
    }
    add() {
        const prototypeHTML = this.element.dataset.prototype;
        if (!prototypeHTML) {
            throw new Error('A "data-prototype" attribute was expected on data-controller="' + this.controllerName + '" element.');
        }
        const collectionNamePattern = this.element.id.replace(/_/g, '(?:_|\\[|]\\[)');
        const newEntry = this._textToNode(prototypeHTML
            .replace(this.prototypeNameValue + 'label__', this.index.toString())
            .replace(new RegExp(`(${collectionNamePattern}(?:_|]\\[))${this.prototypeNameValue}`, 'g'), `$1${this.index.toString()}`));
        this._dispatchEvent('collection:pre-add', {
            entry: newEntry,
            index: this.index,
        });
        const entries = [];
        this.element.querySelectorAll(this.itemSelectorValue
            ? this.itemSelectorValue.replace('%controllerName%', this.controllerName)
            : ':scope > [data-' + this.controllerName + '-target="entry"]:not([data-controller] > *)').forEach(entry => {
            entries.push(entry);
        });
        if (entries.length > 0) {
            entries[entries.length - 1].after(newEntry);
        }
        else {
            this.element.prepend(newEntry);
        }
        this._dispatchEvent('collection:add', {
            entry: newEntry,
            index: this.index,
        });
        this.index++;
    }
    delete(event) {
        const clickTarget = event.target;
        const entry = clickTarget.closest('[data-' + this.controllerName + '-target="entry"]');
        this._dispatchEvent('collection:pre-delete', {
            entry: entry,
        });
        entry.remove();
        this._dispatchEvent('collection:delete', {
            entry: entry,
        });
    }
    _textToNode(text) {
        const template = document.createElement('template');
        text = text.trim();
        template.innerHTML = text;
        return template.content.firstChild;
    }
    _dispatchEvent(name, payload = {}) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
default_1.values = {
    prototypeName: String,
    itemSelector: String,
};

export { default_1 as default };
