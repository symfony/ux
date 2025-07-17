/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import type { Component } from 'vue';
import { type ComponentCollection, components } from './components.js';

declare global {
    function resolveVueComponent(name: string): Component;

    interface Window {
        resolveVueComponent(name: string): Component;
    }
}

export function registerVueControllerComponents(vueControllers: ComponentCollection = components): void {
    function loadComponent(name: string): Component | never {
        const component = vueControllers[name];

        if (typeof component === 'undefined') {
            const possibleValues: string =
                Object.keys(vueControllers).length > 0 ? Object.keys(vueControllers).join(', ') : 'none';

            throw new Error(`Vue controller "${name}" does not exist. Possible values: ${possibleValues}`);
        }

        return component;
    }

    // Expose a global Vue loader to allow rendering from the Stimulus controller
    window.resolveVueComponent = (name: string): Component => {
        return loadComponent(name);
    };
}
