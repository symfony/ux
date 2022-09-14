/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

export function registerVueControllerComponents(contexts: any) {
    const vueControllers: { [key: string]: object } = {};

    const importAllVueComponents = (r: __WebpackModuleApi.RequireContext) => {
        r.keys().forEach((key) => (vueControllers[key] = r(key).default));
    };

    [].concat(contexts).forEach((context: __WebpackModuleApi.RequireContext) => importAllVueComponents(context));

    // Expose a global Vue loader to allow rendering from the Stimulus controller
    (window as any).resolveVueComponent = (name: string): object => {
        const component = Object.values(
            Object.fromEntries(
                Object.entries(vueControllers).filter(([key]) => key.endsWith(`${name}.vue`)))
        )[0];

        if (typeof component === 'undefined') {
            throw new Error(`Vue controller "${name}" does not exist`);
        }

        return component;
    };
}
