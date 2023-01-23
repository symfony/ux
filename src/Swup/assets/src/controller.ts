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
    declare readonly animateHistoryBrowsingValue: boolean;
    declare readonly hasAnimateHistoryBrowsingValue: boolean;
    declare readonly animationSelectorValue: string;
    declare readonly hasAnimationSelectorValue: boolean;
    declare readonly cacheValue: boolean;
    declare readonly hasCacheValue: boolean;
    declare readonly containersValue: string[];
    declare readonly mainElementValue: string;
    declare readonly hasMainElementValue: boolean;
    declare readonly linkSelectorValue: string;
    declare readonly hasLinkSelectorValue: boolean;
    declare readonly themeValue: string;
    declare readonly debugValue: boolean;

    static values = {
        animateHistoryBrowsing: Boolean,
        animationSelector: String,
        cache: Boolean,
        containers: Array,
        linkSelector: String,

        // custom values
        theme: String,
        debug: Boolean,
        mainElement: String,
    };

    connect() {
        const dataContainers = this.containersValue;
        const mainElement = this.mainElementValue || dataContainers[0] || '#swup';
        const allElements = [mainElement].concat(dataContainers);
        const containersList = allElements.filter((item, index) => {
            return allElements.indexOf(item) === index;
        });

        const options: any = {
            containers: containersList,
            plugins: [
                'slide' === this.themeValue
                    ? new SwupSlideTheme({ mainElement: mainElement })
                    : new SwupFadeTheme({ mainElement: mainElement }),
                new SwupFormsPlugin(),
            ],
        };

        if (this.hasMainElementValue) {
            options.mainElement = this.mainElementValue;
        }

        if (this.hasAnimateHistoryBrowsingValue) {
            options.animateHistoryBrowsing = this.animateHistoryBrowsingValue;
        }
        if (this.hasAnimationSelectorValue) {
            options.animationSelector = this.animationSelectorValue;
        }
        if (this.hasCacheValue) {
            options.cache = this.cacheValue;
        }
        if (this.hasLinkSelectorValue) {
            options.linkSelector = this.linkSelectorValue;
        }
        if (this.debugValue) {
            options.plugins.push(new SwupDebugPlugin());
        }

        this.dispatchEvent('pre-connect', { options });

        const swup = new Swup(options);

        this.dispatchEvent('connect', { swup, options });
    }

    private dispatchEvent(name: string, payload: any) {
        this.dispatch(name, { detail: payload, prefix: 'swup' });
    }
}
