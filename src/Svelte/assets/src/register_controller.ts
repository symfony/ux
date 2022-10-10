/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import type { SvelteComponent } from 'svelte';

declare global {
    function resolveSvelteComponent(name: string): typeof SvelteComponent;

    interface Window {
        resolveSvelteComponent(name: string): typeof SvelteComponent;
    }
}

export function registerSvelteControllerComponents(context: __WebpackModuleApi.RequireContext) {
    const svelteControllers: { [key: string]: object } = {};

    const importAllSvelteComponents = (r: __WebpackModuleApi.RequireContext) => {
        r.keys().forEach((key) => (svelteControllers[key] = r(key).default));
    };

    importAllSvelteComponents(context);

    // Expose a global Svelte loader to allow rendering from the Stimulus controller
    (window as any).resolveSvelteComponent = (name: string): object => {
        const component = svelteControllers[`./${name}.svelte`];
        if (typeof component === 'undefined') {
            throw new Error(`Svelte controller "${name}" does not exist`);
        }

        return component;
    };
}
