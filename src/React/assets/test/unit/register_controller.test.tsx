/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/// <reference types="vite/client" />

import { describe, expect, it } from 'vitest';
import { registerReactControllerComponents } from '../../src/register_controller';
// @ts-expect-error
import MyJsxComponent from '../fixtures/MyJsxComponent';
import MyTsxComponent from '../fixtures/MyTsxComponent';

import RequireContext = __WebpackModuleApi.RequireContext;

const createFakeFixturesContext = (): RequireContext => {
    const files: any = {
        './MyJsxComponent.jsx': { default: MyJsxComponent },
        './MyTsxComponent.tsx': { default: MyTsxComponent },
        './NoDefaultExportComponent.jsx': { default: undefined },
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

    it('throws when no default export found in imported module', () => {
        registerReactControllerComponents(createFakeFixturesContext());
        const resolveComponent = (window as any).resolveReactComponent;

        expect(() => resolveComponent('NoDefaultExportComponent')).toThrow(
            'React controller "NoDefaultExportComponent" could not be resolved. Ensure the module exports the controller as a default export.'
        );
    });
});

describe('registerReactControllerComponents with import.meta.glob()', () => {
    it('registers eager components', () => {
        registerReactControllerComponents(import.meta.glob('../fixtures/*.{jsx,tsx}', { eager: true }));
        const resolveComponent = (window as any).resolveReactComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('MyTsxComponent')).toBe(MyTsxComponent);
        expect(resolveComponent('MyJsxComponent')).not.toBeUndefined();
    });

    it('errors with a bad name', () => {
        registerReactControllerComponents(import.meta.glob('../fixtures/*.{jsx,tsx}', { eager: true }));
        const resolveComponent = (window as any).resolveReactComponent;

        expect(() => resolveComponent('MyABCComponent')).toThrow(
            'React controller "MyABCComponent" does not exist. Possible values: MyJsxComponent, MyTsxComponent'
        );
    });

    it('throws a helpful error when given a lazy glob', () => {
        expect(() => registerReactControllerComponents(import.meta.glob('../fixtures/*.{jsx,tsx}'))).toThrow(
            /Enable the "eager" option/
        );
    });
});
