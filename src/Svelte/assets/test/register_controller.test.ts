/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import {registerSvelteControllerComponents} from '../src/register_controller';
import MyComponent from './fixtures/MyComponent.svelte';
import {createRequireContextPolyfill} from './util/require_context_poylfill';

require.context = createRequireContextPolyfill(__dirname);

describe('registerSvelteControllerComponents', () => {
    it('registers controllers from require context', () => {
        registerSvelteControllerComponents(require.context('./fixtures', true, /\.svelte$/));
        const resolveComponent = (window as any).resolveSvelteComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('MyComponent')).toBe(MyComponent);
        expect(resolveComponent('MyComponent')).not.toBeUndefined();
    });
});
