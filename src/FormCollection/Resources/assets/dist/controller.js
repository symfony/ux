import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.index = 0;
        this.controllerName = 'collection';
        this.entries = [];
    }
    connect() {
        this.controllerName = this.context.scope.identifier;
        this._dispatchEvent('form-collection:pre-connect');
        this.entries = [];
        this.element.querySelectorAll(':scope > [data-' + this.controllerName + '-target="entry"]').forEach(entry => {
            this.entries.push(entry);
        });
        this._dispatchEvent('form-collection:connect');
    }
    add() {
        const prototypeHTML = this.element.dataset.prototype;
        if (!prototypeHTML) {
            throw new Error('A "data-prototype" attribute was expected on data-controller="' + this.controllerName + '" element.');
        }
        const newEntry = this._textToNode(prototypeHTML.replace(new RegExp('/' + this.prototypeNameValue + '/', 'g'), this.index.toString()));
        this._dispatchEvent('form-collection:pre-add', {
            entry: newEntry,
            index: this.index,
        });
        if (this.entries.length > 0) {
            this.entries[this.entries.length - 1].after(newEntry);
        }
        else {
            this.element.prepend(newEntry);
        }
        this.entries.push(newEntry);
        this._dispatchEvent('form-collection:add', {
            entry: newEntry,
            index: this.index,
        });
        this.index++;
    }
    delete(event) {
        const clickTarget = event.target;
        const entry = clickTarget.closest('[data-' + this.controllerName + '-target="entry"]');
        this._dispatchEvent('form-collection:pre-delete', {
            entry: entry,
        });
        entry.remove();
        this.entries = this.entries.filter(currentEntry => currentEntry !== entry);
        this._dispatchEvent('form-collection:delete', {
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
};

export { default_1 as default };
