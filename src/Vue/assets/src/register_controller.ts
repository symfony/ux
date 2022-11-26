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
import { defineAsyncComponent } from 'vue';

declare global {
    function resolveVueComponent(name: string): Component;

    interface Window {
        resolveVueComponent(name: string): Component;
    }
}

export function registerVueControllerComponents(context: __WebpackModuleApi.RequireContext) {
    const vueControllers = context.keys().reduce((acc, key) => {
        acc[key] = undefined;
        return acc;
    }, {} as Record<string, object | undefined>);

    function loadComponent(name: string): object | never {
        const componentPath = `./${name}.vue`;

        if (componentPath in vueControllers && typeof vueControllers[componentPath] === 'undefined') {
            const module = context(componentPath);
            if (module.default) {
                vueControllers[componentPath] = module.default;
            } else if (module instanceof Promise) {
                vueControllers[componentPath] = defineAsyncComponent(
                    () =>
                        new Promise((resolve, reject) => {
                            module
                                .then((resolvedModule) => {
                                    if (resolvedModule.default) {
                                        resolve(resolvedModule.default);
                                    } else {
                                        reject(
                                            new Error(`Cannot find default export in async Vue controller "${name}".`)
                                        );
                                    }
                                })
                                .catch(reject);
                        })
                );
            } else {
                throw new Error(`Vue controller "${name}" does not exist.`);
            }
        }

        return vueControllers[componentPath] as object;
    }

    // Expose a global Vue loader to allow rendering from the Stimulus controller
    window.resolveVueComponent = (name: string): object => {
        return loadComponent(name);
    };
}
