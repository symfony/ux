/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import {registerVueControllerComponents} from '../src/register_controller';
import {createRequireContextPolyfill} from './util/require_context_poylfill';
import Hello from './fixtures/common/Hello.vue'
import Entry from './fixtures/entry/Entry.vue'

require.context = createRequireContextPolyfill(__dirname);

describe('registerSingleVueControllerComponentContext', () => {
    it('test', () => {
        registerVueControllerComponents(require.context('./fixtures', true, /\.vue$/));
        const resolveComponent = (window as any).resolveVueComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('Hello')).toBe(Hello);
        expect(resolveComponent('Entry')).toBe(Entry);
    });
});

describe('registerMultipleVueControllerComponentsContexts', () => {
    it('test', () => {
        registerVueControllerComponents([
            require.context('./fixtures/common', true, /\.vue$/),
            require.context('./fixtures/entry', true, /\.vue$/)
        ]);
        const resolveComponent = (window as any).resolveVueComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('Hello')).toBe(Hello);
        expect(resolveComponent('Entry')).toBe(Entry);
    });
});
