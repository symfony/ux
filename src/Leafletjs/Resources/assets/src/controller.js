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

export default class extends Controller {
    static targets = ['placeholder'];

    connect() {
        import('leaflet').then((L) => {
            let mapOptions = this._extractOptions('map-options');

            this.map = L.map(this.placeholderTarget, mapOptions).setView(this._coordinates(), this.data.get('zoom'));

            let layer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            });

            layer.addTo(this.map);

            const map = this.map;
            this._dispatchEvent('leafletjs:connect', { map: map, layer: layer });
        });
    }

    disconnect() {
        this.map.off();
        this.map.remove();
    }

    _extractOptions(dataName) {
        let options = JSON.parse(this.data.get(dataName));
        if (Array.isArray(options) && 0 === options.length) {
            options = {};
        }
        return options;
    }

    _coordinates() {
        return [this.data.get('latitude'), this.data.get('longitude')];
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}
