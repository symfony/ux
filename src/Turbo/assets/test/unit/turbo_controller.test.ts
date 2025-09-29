/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application } from '@hotwired/stimulus';
import { getByTestId } from '@testing-library/dom';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { clearDOM, mountDOM } from '../../../../../test/stimulus-helpers';
import TurboController from '../../src/turbo_controller';

const startStimulus = () => {
    const application = Application.start();
    application.register('symfony--ux-turbo--turbo', TurboController);
};

describe('TurboStreamController', () => {
    let container: HTMLElement;

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
