'use strict';

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets() {
        return ['entry', 'addButton', 'removeButton'];
    }

    declare readonly entryTargets: HTMLElement[];

    index: Number = 0;
    controllerName: string = 'collection';

    connect() {
        this.controllerName = this.context.scope.identifier;

        this._dispatchEvent('form-collection:pre-connect');

        this.index = this.entryTargets.length;

        this._dispatchEvent('form-collection:connect');
    }

    add() {
        const prototype = this.element.dataset.prototype;

        if (!prototype) {
            throw new Error(
                'A "data-prototype" attribute was expected on data-controller="' + this.controllerName + '" element.'
            );
        }

        this._dispatchEvent('form-collection:pre-add', {
            prototype: prototype,
            index: this.index,
        });

        const newEntry = this._textToNode(prototype.replace(/__name__/g, this.index.toString()));

        if (this.entryTargets.length > 1) {
            this.entryTargets[this.entryTargets.length - 1].after(newEntry);
        } else {
            this.element.prepend(newEntry);
        }

        this._dispatchEvent('form-collection:add', {
            prototype: prototype,
            index: this.index,
        });

        this.index++;
    }

    delete(event: MouseEvent) {
        const clickTarget = event.target as HTMLButtonElement;

        const entry = clickTarget.closest('[data-' + this.controllerName + '-target="entry"]') as HTMLElement;

        this._dispatchEvent('form-collection:pre-delete', {
            element: entry,
        });

        entry.remove();

        this._dispatchEvent('form-collection:delete', {
            element: entry,
        });
    }

    _textToNode(text: string): HTMLElement {
        const template = document.createElement('template');
        text = text.trim();

        template.innerHTML = text;

        return template.content.firstChild as HTMLElement;
    }

    _dispatchEvent(name: string, payload: {} = {}) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
