'use strict';

import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = [
        'container',
        'entry'
    ];

    static values = {
        allowAdd: Boolean,
        allowDelete: Boolean,
        buttonAdd: String,
        buttonDelete: String,
        prototypeName: String
    };

    /**
     * Number of elements for the index of the collection
     * @type Number
     */
    index = 0;

    /**
     * Controller name of this
     * @type String|null
     */
    controllerName = null;

    connect() {
        this.controllerName = this.context.scope.identifier;


        this._dispatchEvent('form-collection:pre-connect', {
            allowAdd: this.allowAddValue,
            allowDelete: this.allowDeleteValue,
        });

        if (true === this.allowAddValue) {
            // Add button Add
            let buttonAdd = this._textToNode(this.buttonAddValue);
            this.containerTarget.prepend(buttonAdd);
        }

        // Add buttons Delete
        if (true === this.allowDeleteValue) {
            for (let i = 0; i < this.entryTargets.length; i++) {
                this.index = i;
                let entry = this.entryTargets[i];
                this._addDeleteButton(entry, this.index);
            }
        }

        this._dispatchEvent('form-collection:connect', {
            allowAdd: this.allowAddValue,
            allowDelete: this.allowDeleteValue,
        });
    }

    add(event) {

        this.index++;

        // Compute the new entry
        let newEntry = this.containerTarget.dataset.prototype;
        
        let regExp = new RegExp(this.prototypeNameValue+'label__', 'g');
        newEntry = newEntry.replace(regExp, this.index);

        regExp = new RegExp(this.prototypeNameValue, 'g');
        newEntry = newEntry.replace(regExp, this.index);

        newEntry = this._textToNode(newEntry);

        this._dispatchEvent('form-collection:pre-add', {
            index: this.index,
            element: newEntry
        });

        this.containerTarget.append(newEntry);

        // Retrieve the entry from targets to make sure that this is the one
        let entry = this.entryTargets[this.entryTargets.length - 1];
        entry = this._addDeleteButton(entry, this.index);

        this._dispatchEvent('form-collection:add', {
            index: this.index,
            element: entry
        });
    }

    delete(event) {

        let theIndexEntryToDelete = event.target.dataset.indexEntry;

        // Search the entry to delete from the data-index-entry attribute
        for (let i = 0; i < this.entryTargets.length; i++) {
            let entry = this.entryTargets[i];
            if (theIndexEntryToDelete === entry.dataset.indexEntry) {

                this._dispatchEvent('form-collection:pre-delete', {
                    index: entry.dataset.indexEntry,
                    element: entry
                });

                entry.remove();

                this._dispatchEvent('form-collection:delete', {
                    index: entry.dataset.indexEntry,
                    element: entry
                });
            }
        }
    }

    /**
     * Add the delete button to the entry
     * @param String entry
     * @param Number index
     * @returns {ChildNode}
     * @private
     */
    _addDeleteButton(entry, index) {

        // link the button and the entry by the data-index-entry attribute
        entry.dataset.indexEntry = index;

        let buttonDelete = this._textToNode(this.buttonDeleteValue);
        buttonDelete.dataset.indexEntry = index;

        if('TR' === entry.nodeName) {
            entry.lastElementChild.append(buttonDelete);
        } else {
            entry.append(buttonDelete);
        }

        return entry;
    }

    /**
     * Convert text to Element to insert in the DOM
     * @param String text
     * @returns {ChildNode}
     * @private
     */
    _textToNode(text) {

        let template = document.createElement('template');
        text = text.trim(); // Never return a text node of whitespace as the result
        template.innerHTML = text;

        return template.content.firstChild;
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
