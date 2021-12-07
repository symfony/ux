/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application, Controller } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import SwupController from '../src/controller';

let actualSwupOptions: any = null;

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('swup:pre-connect', (event) => {
            actualSwupOptions = event.detail.options;
        });

        this.element.addEventListener('swup:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('swup', SwupController);
};

describe('SwupController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM(`
            <html>
                <head>
                    <title>Symfony UX</title>
                </head>
                <body>
                    <div 
                        data-testid="body"
                        data-controller="check swup"
                        data-swup-containers-value="[&quot;#swup&quot;, &quot;#nav&quot;]"
                        data-swup-link-selector-value="a"
                        data-swup-animation-selector-value="[transition-*]"
                        data-swup-debug-value="data-debug"
                        data-swup-cache-value="data-cache"
                        data-swup-animate-history-browsing-value="data-animate-history-browsing">
                        <div id="nav"></div>
                        <div id="swup">
                            <a href="/">Link</a>
                        </div>
                    </div>
                </body>
            </html>
        `);
    });

    afterEach(() => {
        clearDOM();
        actualSwupOptions = null;
    });

    it('connect', async () => {
        const bodyElement = getByTestId(container, 'body');
        expect(bodyElement).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(bodyElement).toHaveClass('connected'));
        expect(actualSwupOptions.containers).toEqual(['#swup', '#nav']);
        expect(actualSwupOptions.linkSelector).toBe('a');
        expect(actualSwupOptions.animationSelector).toBe('[transition-*]');
        expect(actualSwupOptions.cache).toBe(true);
        expect(actualSwupOptions.animateHistoryBrowsing).toBe(true);
    });
});
