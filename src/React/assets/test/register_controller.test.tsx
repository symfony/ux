/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import {registerReactControllerComponents} from '../src/register_controller';
import MyTsxComponent from './fixtures/MyTsxComponent';
import {createRequireContextPolyfill} from './util/require_context_poylfill';

require.context = createRequireContextPolyfill(__dirname);

describe('registerReactControllerComponents', () => {
    it('test', () => {
        registerReactControllerComponents(require.context('./fixtures', true, /\.(j|t)sx$/));
        const resolveComponent = (window as any).resolveReactComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('MyTsxComponent')).toBe(MyTsxComponent);
        expect(resolveComponent('MyJsxComponent')).not.toBeUndefined();
    });

    it('errors with a bad name', () => {
        registerReactControllerComponents(require.context('./fixtures', true, /\.(j|t)sx$/));
        const resolveComponent = (window as any).resolveReactComponent;

        expect(() => resolveComponent('MyABCComponent')).toThrow('React controller "MyABCComponent" does not exist. Possible values: MyJsxComponent, MyTsxComponent');
    });
});
