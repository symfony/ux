/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from '@hotwired/stimulus'

// Controller used to check the actual controller was properly booted
export class CheckController extends Controller {
    connect() {
        this.element.addEventListener('chartjs:pre-connect', () => {
            this.element.classList.add('pre-connected');
        });

        this.element.addEventListener('chartjs:connect', (event) => {
            this.element.classList.add('connected');
            this.element.chart = event.detail.chart;
        });
    }
}
