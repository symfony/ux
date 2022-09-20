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
import Typed from 'typed.js';

export default class extends Controller {
    static values = {
        strings: Array,
        typeSpeed: { type: Number, default: 30 },
        smartBackspace: { type: Boolean, default: true },
        startDelay: Number,
        backSpeed: Number,
        shuffle: Boolean,
        backDelay: { type: Number, default: 700 },
        fadeOut: Boolean,
        fadeOutClass: { type: String, default: 'typed-fade-out' },
        fadeOutDelay: { type: Number, default: 500 },
        loop: Boolean,
        loopCount: { type: Number, default: Infinity },
        showCursor: { type: Boolean, default: true },
        cursorChar: { type: String, default: '.' },
        autoInsertCss: { type: Boolean, default: true },
        attr: String,
        bindInputFocusEvents: Boolean,
        contentType: { type: String, default: 'html' },
    };

    readonly stringsValue!: string[];
    readonly typeSpeedValue: number;
    readonly smartBackspaceValue: boolean;
    readonly startDelayValue?: number;
    readonly backSpeedValue?: number;
    readonly shuffleValue?: boolean;
    readonly backDelayValue: number;
    readonly fadeOutValue?: boolean;
    readonly fadeOutClassValue: string;
    readonly fadeOutDelayValue: number;
    readonly loopValue?: boolean;
    readonly loopCountValue: number;
    readonly showCursorValue: boolean;
    readonly cursorCharValue: string;
    readonly autoInsertCssValue: boolean;
    readonly attrValue?: string;
    readonly bindInputFocusEventsValue?: boolean;
    readonly contentTypeValue: string;

    connect() {
        const options = {
            strings: this.stringsValue,
            typeSpeed: this.typeSpeedValue,
            smartBackspace: this.smartBackspaceValue,
            startDelay: this.startDelayValue,
            backSpeed: this.backSpeedValue,
            shuffle: this.shuffleValue,
            backDelay: this.backDelayValue,
            fadeOut: this.fadeOutValue,
            fadeOutClass: this.fadeOutClassValue,
            fadeOutDelay: this.fadeOutDelayValue,
            loop: this.loopValue,
            loopCount: this.loopCountValue,
            showCursor: this.showCursorValue,
            cursorChar: this.cursorCharValue,
            autoInsertCss: this.autoInsertCssValue,
            attr: this.attrValue,
            bindInputFocusEvents: this.bindInputFocusEventsValue,
            contentType: this.contentTypeValue,
        };

        this._dispatchEvent('typed:pre-connect', { options });
        const typed = new Typed(this.element, options);
        this._dispatchEvent('typed:connect', { typed, options });
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
