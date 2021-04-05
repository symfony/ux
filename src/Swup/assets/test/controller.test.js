/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application, Controller } from 'stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import SwupController from '../dist/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
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
                        data-containers="#swup #nav"
                        data-link-selector="a"
                        data-animation-selector="[transition-*]"
                        data-debug="data-debug"
                        data-cache="data-cache"
                        data-animate-history-browsing="data-animate-history-browsing">
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
    });

    it('connect', async () => {
        expect(getByTestId(container, 'body')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'body')).toHaveClass('connected'));
    });
});
