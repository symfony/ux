/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import type { SvelteComponent } from 'svelte';
import { type ComponentCollection, components } from './components.js';

declare global {
    function resolveSvelteComponent(name: string): typeof SvelteComponent<any>;

    interface Window {
        resolveSvelteComponent(name: string): typeof SvelteComponent<any>;
    }
}

export function registerSvelteControllerComponents(svelteComponents: ComponentCollection = components): void {
    // Expose a global Svelte loader to allow rendering from the Stimulus controller
    (window as any).resolveSvelteComponent = (name: string): SvelteComponent => {
        const component = svelteComponents[name];
        if (typeof component === 'undefined') {
            const possibleValues: string =
                Object.keys(svelteComponents).length > 0 ? Object.keys(svelteComponents).join(', ') : 'none';

            throw new Error(`Svelte controller "${name}" does not exist. Possible values: ${possibleValues}`);
        }

        return component;
    };
}
