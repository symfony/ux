'use strict';

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['entry'];

    static values = {
        allowAdd: Boolean,
        allowDelete: Boolean,
        buttonAdd: String,
        buttonDelete: String,
        prototypeName: String,
        prototype: String,
        startIndex: Number,
    };

    allowAddValue: boolean;
    allowDeleteValue: boolean;
    buttonAddValue: string;
    buttonDeleteValue: string;
    prototypeNameValue: string;
    prototypeValue: string;
    startIndexValue: number;

    /**
     * Number of elements for the index of the collection
     */
    index = 0;

    controllerName: string;

    entryTargets: Array<any> = [];

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
            // Add button Add
            const buttonAdd = this._textToNode(this.buttonAddValue);
            this.element.prepend(buttonAdd);
        }

        // Add buttons Delete
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

        // Compute the new entry
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

        // Retrieve the entry from targets to make sure that this is the one
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

    /**
     * Add the delete button to the entry
     * @private
     */
    _addDeleteButton(entry: HTMLElement, index: number) {
        // link the button and the entry by the data-index-entry attribute
        entry.dataset.indexEntry = index.toString();

        const buttonDelete = this._textToNode(this.buttonDeleteValue);
        if (!buttonDelete) {
            return entry;
        }
        buttonDelete.dataset.indexEntry = index;

        if ('TR' === entry.nodeName) {
            entry.lastElementChild.append(buttonDelete);
        } else {
            entry.append(buttonDelete);
        }

        return entry;
    }

    /**
     * Convert text to Element to insert in the DOM
     * @private
     */
    _textToNode(text: string) {
        const template = document.createElement('template');
        text = text.trim(); // Never return a text node of whitespace as the result
        template.innerHTML = text;

        return template.content.firstChild;
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
