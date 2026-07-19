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
import { registerVueControllerComponents } from '../../src/register_controller';
import Hello from '../fixtures/Hello.vue';
import Goodbye from '../fixtures-lazy/Goodbye.vue';

import RequireContext = __WebpackModuleApi.RequireContext;

const createFakeFixturesContext = (lazyDir: boolean): RequireContext => {
    const files: any = {};

    if (lazyDir) {
        files['./Goodbye.vue'] = { default: Goodbye };
    } else {
        files['./Hello.vue'] = { default: Hello };
    }

    const context = (id: string): any => files[id];
    context.keys = () => Object.keys(files);
    context.resolve = (id: string) => id;
    context.id = './fixtures';

    return context;
};

describe('registerVueControllerComponents', () => {
    it('should resolve components synchronously', () => {
        registerVueControllerComponents(createFakeFixturesContext(false));
        const resolveComponent = window.resolveVueComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('Hello')).toBe(Hello);
    });

    it('should resolve lazy components asynchronously', () => {
        registerVueControllerComponents(createFakeFixturesContext(true));
        const resolveComponent = window.resolveVueComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('Goodbye')).toBe(Goodbye);
    });

    it('errors with a bad name', () => {
        registerVueControllerComponents(createFakeFixturesContext(false));
        const resolveComponent = window.resolveVueComponent;

        expect(() => resolveComponent('Helloooo')).toThrow(
            'Vue controller "Helloooo" does not exist. Possible values: Hello'
        );
    });
});

describe('registerVueControllerComponents with import.meta.glob()', () => {
    it('should resolve eager components synchronously', () => {
        registerVueControllerComponents(import.meta.glob('../fixtures/*.vue', { eager: true }));
        const resolveComponent = window.resolveVueComponent;

        expect(resolveComponent).not.toBeUndefined();
        expect(resolveComponent('Hello')).toBe(Hello);
    });

    it('should resolve lazy components asynchronously', () => {
        registerVueControllerComponents(import.meta.glob('../fixtures-lazy/*.vue'));
        const resolveComponent = window.resolveVueComponent;

        expect(resolveComponent).not.toBeUndefined();
        // A lazy glob is wrapped in an async component, so it is not the raw component.
        const component = resolveComponent('Goodbye');
        expect(component).toBeDefined();
        expect(component).not.toBe(Goodbye);
    });

    it('errors with a bad name', () => {
        registerVueControllerComponents(import.meta.glob('../fixtures/*.vue', { eager: true }));
        const resolveComponent = window.resolveVueComponent;

        expect(() => resolveComponent('Helloooo')).toThrow(
            'Vue controller "Helloooo" does not exist. Possible values: Hello'
        );
    });
});
