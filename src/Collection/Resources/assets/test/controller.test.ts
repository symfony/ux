/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application } from '@hotwired/stimulus';
import { getByTestId } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import CollectionController from '../src/controller';

const startStimulus = () => {
    const application = Application.start();
    application.register('symfony--ux-collection--collection', CollectionController);
};

/* eslint-disable no-undef */
describe('CollectionController', () => {
    let container: any;

    beforeEach(() => {
        container = mountDOM('<div data-testid="collection" data-controller="symfony--ux-collection--collection"></div>');
    });

    afterEach(() => {
        clearDOM();
    });

    it('connects', async () => {
        startStimulus();

        // smoke test
        expect(getByTestId(container, 'collection')).toHaveAttribute('data-controller', 'symfony--ux-collection--collection');
    });
});
