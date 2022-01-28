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
    animateHistoryBrowsingValue: boolean;
    hasAnimateHistoryBrowsingValue: boolean;
    animationSelectorValue: string;
    hasAnimationSelectorValue: boolean;
    cacheValue: boolean;
    hasCacheValue: boolean;
    containersValue: string[];
    hasContainersValue: boolean;
    linkSelectorValue: string;
    hasLinkSelectorValue: boolean;
    themeValue: string;
    debugValue: boolean;

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
        const options: any = {
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

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
