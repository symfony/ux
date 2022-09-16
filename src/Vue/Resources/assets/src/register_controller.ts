/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import type { Component } from 'vue';

declare global {
    function resolveVueComponent(name: string): Component;

    interface Window {
        resolveVueComponent(name: string): Component;
    }
}

export function registerVueControllerComponents(context: __WebpackModuleApi.RequireContext) {
    const vueControllers: { [key: string]: object } = {};

    const importAllVueComponents = (r: __WebpackModuleApi.RequireContext) => {
        r.keys().forEach((key) => (vueControllers[key] = r(key).default));
    };

    importAllVueComponents(context);

    // Expose a global Vue loader to allow rendering from the Stimulus controller
    window.resolveVueComponent = (name: string): object => {
        const component = vueControllers[`./${name}.vue`];
        if (typeof component === 'undefined') {
            throw new Error(`Vue controller "${name}" does not exist`);
        }

        return component;
    };
}
