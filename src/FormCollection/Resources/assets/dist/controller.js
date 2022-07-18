import { Controller } from '@hotwired/stimulus';

class controller extends Controller {
    constructor() {
        super(...arguments);
        this.index = 0;
        this.controllerName = 'collection';
    }
    static get targets() {
        return ['entry', 'addButton', 'removeButton'];
    }
    connect() {
        this.controllerName = this.context.scope.identifier;
        this._dispatchEvent('form-collection:pre-connect');
        this.index = this.entryTargets.length;
        this._dispatchEvent('form-collection:connect');
    }
    add() {
        const prototype = this.element.dataset.prototype;
        if (!prototype) {
            throw new Error('A "data-prototype" attribute was expected on data-controller="' + this.controllerName + '" element.');
        }
        this._dispatchEvent('form-collection:pre-add', {
            prototype: prototype,
            index: this.index,
        });
        const newEntry = this._textToNode(prototype.replace(/__name__/g, this.index.toString()));
        if (this.entryTargets.length > 1) {
            this.entryTargets[this.entryTargets.length - 1].after(newEntry);
        }
        else {
            this.element.prepend(newEntry);
        }
        this._dispatchEvent('form-collection:add', {
            prototype: prototype,
            index: this.index,
        });
        this.index++;
    }
    delete(event) {
        const clickTarget = event.target;
        const entry = clickTarget.closest('[data-' + this.controllerName + '-target="entry"]');
        this._dispatchEvent('form-collection:pre-delete', {
            element: entry,
        });
        entry.remove();
        this._dispatchEvent('form-collection:delete', {
            element: entry,
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

export { controller as default };
