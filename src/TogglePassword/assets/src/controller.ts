/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from '@hotwired/stimulus';

export default class extends Controller<HTMLInputElement> {
    declare readonly visibleLabelValue: string;
    declare readonly visibleIconValue: string;
    declare readonly hiddenLabelValue: string;
    declare readonly hiddenIconValue: string;
    declare readonly buttonClassesValue: Array<string>;

    static values = {
        visibleLabel: String,
        visibleIcon: String,
        hiddenLabel: String,
        hiddenIcon: String,
        buttonClasses: Array,
    };

    isDisplayed = false;
    visibleIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="toggle-password-icon" viewBox="0 0 20 20" fill="currentColor">
<path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
<path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
</svg>`;
    hiddenIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="toggle-password-icon" viewBox="0 0 20 20" fill="currentColor">
<path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
<path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
</svg>`;

    connect() {
        if (this.visibleIconValue !== 'Default') {
            this.visibleIcon = this.visibleIconValue;
        }
        if (this.hiddenIconValue !== 'Default') {
            this.hiddenIcon = this.hiddenIconValue;
        }
        const button = this.createButton();
        this.element.insertAdjacentElement('afterend', button);
        this.dispatchEvent('connect', { element: this.element, button: button });
    }

    /**
     * @returns {HTMLButtonElement}
     */
    private createButton(): HTMLButtonElement {
        const button: HTMLButtonElement = document.createElement('button');
        button.type = 'button';
        button.classList.add(...this.buttonClassesValue);
        button.setAttribute('tabindex', '-1');
        button.addEventListener('click', this.toggle.bind(this));
        button.innerHTML = this.visibleIcon + ' ' + this.visibleLabelValue;

        return button;
    }

    /**
     * Toggle input type between "text" or "password" and update label accordingly
     */
    toggle(event: any): void {
        this.isDisplayed = !this.isDisplayed;
        const toggleButtonElement: HTMLButtonElement = event.currentTarget;
        toggleButtonElement.innerHTML = this.isDisplayed
            ? this.hiddenIcon + ' ' + this.hiddenLabelValue
            : this.visibleIcon + ' ' + this.visibleLabelValue;
        this.element.setAttribute('type', this.isDisplayed ? 'text' : 'password');
        this.dispatchEvent(this.isDisplayed ? 'show' : 'hide', { element: this.element, button: toggleButtonElement });
    }

    private dispatchEvent(name: string, payload: any): void {
        this.dispatch(name, { detail: payload, prefix: 'toggle-password' });
    }
}
