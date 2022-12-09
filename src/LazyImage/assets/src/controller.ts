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

export default class extends Controller {
    declare readonly srcValue: string;
    declare readonly srcsetValue: any;
    declare readonly hasSrcsetValue: boolean;

    static values = {
        src: String,
        srcset: Object,
    };

    connect() {
        const hd = new Image();
        const element = this.element as HTMLImageElement;

        const srcsetString = this._calculateSrcsetString();

        hd.addEventListener('load', () => {
            element.src = this.srcValue;
            if (srcsetString) {
                element.srcset = srcsetString;
            }
            this._dispatchEvent('lazy-image:ready', { image: hd });
        });

        hd.src = this.srcValue;
        if (srcsetString) {
            hd.srcset = srcsetString;
        }

        this._dispatchEvent('lazy-image:connect', { image: hd });
    }

    _calculateSrcsetString(): string {
        if (!this.hasSrcsetValue) {
            return '';
        }

        const sets = Object.keys(this.srcsetValue).map((size: string) => {
            return `${this.srcsetValue[size]} ${size}`;
        });

        return sets.join(', ').trimEnd();
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
