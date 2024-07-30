/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { registerReactControllerComponents } from '../src/register_controller';
import MyTsxComponent from './fixtures/MyTsxComponent';
// @ts-ignore
import MyJsxComponent from './fixtures/MyJsxComponent';
import RequireContext = __WebpackModuleApi.RequireContext;

const createFakeFixturesContext = (): RequireContext => {
    const files: any = {
        './MyJsxComponent.jsx': { default: MyJsxComponent },
        './MyTsxComponent.tsx': { default: MyTsxComponent },
    };

    const context = (id: string): any => files[id];
    context.keys = () => Object.keys(files);
    context.resolve = (id: string) => id;
    context.id = './fixtures';

    return context;
};

describe('registerReactControllerComponents', () => {
    it('test working setup', () => {
        registerReactControllerComponents(createFakeFixturesContext());
        const resolveComponent = (window as any).resolveReactComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('MyTsxComponent')).toBe(MyTsxComponent);
        expect(resolveComponent('MyJsxComponent')).not.toBeUndefined();
    });

    it('errors with a bad name', () => {
        registerReactControllerComponents(createFakeFixturesContext());
        const resolveComponent = (window as any).resolveReactComponent;

        expect(() => resolveComponent('MyABCComponent')).toThrow(
            'React controller "MyABCComponent" does not exist. Possible values: MyJsxComponent, MyTsxComponent'
        );
    });
});
