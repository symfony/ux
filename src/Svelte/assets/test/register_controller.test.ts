/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { registerSvelteControllerComponents } from '../src/register_controller';
import MyComponent from './fixtures/MyComponent.svelte';
import MyComponentSvelte5 from './fixtures/MyComponentSvelte5.svelte';
import RequireContext = __WebpackModuleApi.RequireContext;

const createFakeFixturesContext = (): RequireContext => {
    const files: any = {
        './MyComponent.svelte': { default: MyComponent },
        './MyComponentSvelte5.svelte': { default: MyComponentSvelte5 },
    };

    const context = (id: string): any => files[id];
    context.keys = () => Object.keys(files);
    context.resolve = (id: string) => id;
    context.id = './fixtures';

    return context;
};

describe('registerSvelteControllerComponents', () => {
    it('registers controllers from require context', () => {
        registerSvelteControllerComponents(createFakeFixturesContext());
        const resolveComponent = (window as any).resolveSvelteComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('MyComponent')).toBe(MyComponent);
        expect(resolveComponent('MyComponent')).not.toBeUndefined();
        expect(resolveComponent('MyComponentSvelte5')).toBe(MyComponentSvelte5);
        expect(resolveComponent('MyComponentSvelte5')).not.toBeUndefined();
    });
});
