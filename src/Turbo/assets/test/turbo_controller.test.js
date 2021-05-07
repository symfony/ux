/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application } from 'stimulus';
import { getByTestId } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import TurboController from '../src/turbo_controller';

const startStimulus = () => {
    const application = Application.start();
    application.register('symfony--ux-turbo--turbo', TurboController);
};

/* eslint-disable no-undef */
describe('TurboStreamController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM('<div data-testid="turbo-core" data-controller="symfony--ux-turbo--turbo"></div>');
    });

    afterEach(() => {
        clearDOM();
    });

    it('connects', async () => {
        startStimulus();

        // smoke test
        expect(getByTestId(container, 'turbo-core')).toHaveAttribute('data-controller', 'symfony--ux-turbo--turbo');
    });
});
