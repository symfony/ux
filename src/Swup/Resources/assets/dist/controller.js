import { Controller } from '@hotwired/stimulus';
import Swup from 'swup';
import SwupDebugPlugin from '@swup/debug-plugin';
import SwupFormsPlugin from '@swup/forms-plugin';
import SwupFadeTheme from '@swup/fade-theme';
import SwupSlideTheme from '@swup/slide-theme';

class default_1 extends Controller {
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
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
default_1.values = {
    animateHistoryBrowsing: Boolean,
    animationSelector: String,
    cache: Boolean,
    containers: Array,
    linkSelector: String,
    theme: String,
    debug: Boolean,
};

export { default_1 as default };
