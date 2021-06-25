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
import TypedController from '../dist/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('typed:connect', () => {
            this.element.classList.add('connected');
        });
        this.element.addEventListener('typed:onBegin', () => {
            this.element.classList.add('onBegin');
        });
        this.element.addEventListener('typed:onComplete', () => {
            this.element.classList.add('onComplete');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('typed', TypedController);
};

describe('TypedController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM(`
            <html>
                <head>
                    <title>Symfony UX</title>
                </head>
                <body>                    
                    <div>
                        I created this UX component because <span {{ stimulus_controller("typed", {
                            strings: ["I ❤ Symfony UX", "Symfony UX is great", "Symfony UX is easy"],
                            smartBackspace: true,
                            startDelay: 100,
                            backSpeed: 20,
                            backDelay: 100,
                            loop: true,
                            showCursor: true,
                            cursorChar: "✨"
                        })
                        }}></span>
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
