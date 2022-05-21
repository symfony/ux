import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.index = 0;
    }
    connect() {
        this.controllerName = this.context.scope.identifier;
        this.index = this.startIndexValue ? this.startIndexValue : this.entryTargets.length - 1;
        if (!this.prototypeNameValue) {
            this.prototypeNameValue = '__name__';
        }
        this._dispatchEvent('form-collection:pre-connect', {
            allowAdd: this.allowAddValue,
            allowDelete: this.allowDeleteValue,
        });
        if (this.allowAddValue) {
            const buttonAdd = this._textToNode(this.buttonAddValue);
            this.element.prepend(buttonAdd);
        }
        if (this.allowDeleteValue) {
            for (let i = 0; i < this.entryTargets.length; i++) {
                const entry = this.entryTargets[i];
                this._addDeleteButton(entry, i);
            }
        }
        this._dispatchEvent('form-collection:connect', {
            allowAdd: this.allowAddValue,
            allowDelete: this.allowDeleteValue,
        });
    }
    add() {
        this.index++;
        let newEntry = this.element.dataset.prototype;
        if (!newEntry) {
            newEntry = this.prototypeValue;
        }
        let regExp = new RegExp(this.prototypeNameValue + 'label__', 'g');
        newEntry = newEntry.replace(regExp, this.index);
        regExp = new RegExp(this.prototypeNameValue, 'g');
        newEntry = newEntry.replace(regExp, this.index);
        newEntry = this._textToNode(newEntry);
        this._dispatchEvent('form-collection:pre-add', {
            index: this.index,
            element: newEntry,
        });
        this.element.append(newEntry);
        let entry = this.entryTargets[this.entryTargets.length - 1];
        entry = this._addDeleteButton(entry, this.index);
        this._dispatchEvent('form-collection:add', {
            index: this.index,
            element: entry,
        });
    }
    delete(event) {
        const entry = event.target.closest('[data-' + this.controllerName + '-target="entry"]');
        this._dispatchEvent('form-collection:pre-delete', {
            index: entry.dataset.indexEntry,
            element: entry,
        });
        entry.remove();
        this._dispatchEvent('form-collection:delete', {
            index: entry.dataset.indexEntry,
            element: entry,
        });
    }
    _addDeleteButton(entry, index) {
        entry.dataset.indexEntry = index.toString();
        const buttonDelete = this._textToNode(this.buttonDeleteValue);
        if (!buttonDelete) {
            return entry;
        }
        buttonDelete.dataset.indexEntry = index;
        if ('TR' === entry.nodeName) {
            entry.lastElementChild.append(buttonDelete);
        }
        else {
            entry.append(buttonDelete);
        }
        return entry;
    }
    _textToNode(text) {
        const template = document.createElement('template');
        text = text.trim();
        template.innerHTML = text;
        return template.content.firstChild;
    }
    _dispatchEvent(name, payload) {
        console.log('TTTT');
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
default_1.targets = ['entry'];
default_1.values = {
    allowAdd: Boolean,
    allowDelete: Boolean,
    buttonAdd: String,
    buttonDelete: String,
    prototypeName: String,
    prototype: String,
    startIndex: Number,
};

export { default_1 as default };
