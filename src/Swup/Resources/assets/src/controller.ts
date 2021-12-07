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
import Swup from 'swup';
import SwupDebugPlugin from '@swup/debug-plugin';
import SwupFormsPlugin from '@swup/forms-plugin';
import SwupFadeTheme from '@swup/fade-theme';
import SwupSlideTheme from '@swup/slide-theme';

export default class extends Controller {
    static values = {
        animateHistoryBrowsing: Boolean,
        animationSelector: String,
        cache: Boolean,
        containers: Array,
        linkSelector: String,

        // custom values
        theme: String,
        debug: Boolean,
    };

    connect() {
        const options = {
            containers: ['#swup'],
            plugins: ['slide' === this.themeValue ? new SwupSlideTheme() : new SwupFadeTheme(), new SwupFormsPlugin()],
        };

        if (this.hasAnimateHistoryBrowsingValue) {
            options.animateHistoryBrowsing = this.animateHistoryBrowsingValue;
        }
        if (this.hasAnimationSelectorValue) {
            options.animationSelector = this.animationSelectorValue;
        }
        if (this.hasCacheValue) {
            options.cache = this.cacheValue;
        }
        if (this.hasContainersValue) {
            options.containers = this.containersValue;
        }
        if (this.hasLinkSelectorValue) {
            options.linkSelector = this.linkSelectorValue;
        }
        if (this.debugValue) {
            options.plugins.push(new SwupDebugPlugin());
        }

        this._dispatchEvent('swup:pre-connect', { options });

        const swup = new Swup(options);

        this._dispatchEvent('swup:connect', { swup, options });
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
