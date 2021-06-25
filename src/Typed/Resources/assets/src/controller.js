/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from 'stimulus';
import Typed from "typed.js";

export default class extends Controller {

    static values = {
        strings: Array,
        typeSpeed: Number,
        smartBackspace: Boolean,
        startDelay: Number,
        backSpeed: Number,
        shuffle: Boolean,
        backDelay: Number,
        fadeOut: Boolean,
        fadeOutClass: String,
        fadeOutDelay: Number,
        loop: Boolean,
        loopCount: Number,
        showCursor: Boolean,
        cursorChar: String,
        autoInsertCss: Boolean,
        attr: String,
        bindInputFocusEvents: Boolean,
        contentType: String,
    }

    connect() {
        const options = {
            strings: this.stringsValue || null,
            typeSpeed: this.typeSpeedValue || 30,
            smartBackspace: this.smartBackspaceValue || true,
            startDelay: this.startDelayValue || 0,
            backSpeed: this.backSpeedValue || 0,
            shuffle: this.shuffleValue || false,
            backDelay: this.backDelayValue || 700,
            fadeOut: this.fadeOutValue || false,
            fadeOutClass: this.fadeOutClassValue || 'typed-fade-out',
            fadeOutDelay: this.fadeOutDelayValue || 500,
            loop: this.loopValue || false,
            loopCount: this.loopCountValue || Infinity,
            showCursor: this.showCursorValue || true,
            cursorChar: this.cursorCharValue || '|',
            autoInsertCss: this.autoInsertCssValue || true,
            attr: this.attrValue || null,
            bindInputFocusEvents: this.bindInputFocusEventsValue || false,
            contentType: this.contentTypeValue || 'html',
            onBegin: this.onBegin,
            onComplete: this.onComplete,
            preStringTyped: this.preStringTyped,
            onStringTyped: this.onStringTyped,
            onLastStringBackspaced: this.onLastStringBackspaced,
            onTypingPaused: this.onTypingPaused,
            onTypingResumed: this.onTypingResumed,
            onReset: this.onReset,
            onStop: this.onStop,
            onStart: this.onStart,
            onDestroy: this.onDestroy,
        };

        const typed = new Typed(this.element, options);
        this._object = typed;
        this._dispatchEvent('typed:connect', { typed, options });
    }

    toggle(event)
    {
        console.log('toggle')
        this._object.toggle();
    }

    start(event)
    {
        console.log('start')
        this._object.start();
    }

    stop(event)
    {
        console.log('stop')
        this._object.stop();
    }

    reset(event)
    {
        console.log('reset')
        this._object.reset();
    }

    destroy(event)
    {
        console.log('destroy')
        this._object.destroy();
    }

    disconnect() {
        this._object.destroy()
    }

    onBegin () {}
    onComplete () {console.log(this._object)}
    preStringTyped () {}
    onStringTyped () {}
    onLastStringBackspaced () {}
    onTypingPaused () {}
    onTypingResumed () {}
    onReset () {}
    onStop () {}
    onStart () {}
    onDestroy () {}

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
