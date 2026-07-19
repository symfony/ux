/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/// <reference types="@types/webpack-env" />

import type { Component } from 'vue';
import { defineAsyncComponent } from 'vue';

// A single entry from Vite's / Rsbuild's `import.meta.glob()`: an eagerly-imported
// module, or a function returning one for a lazy glob.
type GlobModule = { default: Component };
type GlobResult = Record<string, GlobModule | (() => Promise<GlobModule>)>;

declare global {
    function resolveVueComponent(name: string): Component;

    interface Window {
        resolveVueComponent(name: string): Component;
    }
}

export function registerVueControllerComponents(context: __WebpackModuleApi.RequireContext | GlobResult) {
    // Normalize both `require.context()` and `import.meta.glob()` to a common map of
    // "./<name>.vue" => loader returning the module (synchronously or as a Promise).
    const loaders: Record<string, () => any> = {};

    if (typeof context === 'function') {
        // Webpack's `require.context()`
        context.keys().forEach((key) => {
            loaders[key] = () => context(key);
        });
    } else {
        // Vite's / Rsbuild's `import.meta.glob()`, either eager (module) or lazy (() => Promise<module>)
        const keyMapping = normalizeGlobKeys(Object.keys(context));
        Object.entries(context).forEach(([key, value]) => {
            loaders[keyMapping[key]] = typeof value === 'function' ? value : () => value;
        });
    }

    // Resolved-component cache, populated lazily on first resolution. Keys mirror `loaders`.
    const vueControllers: Record<string, object | undefined> = {};

    function loadComponent(name: string): object | never {
        const componentPath = `./${name}.vue`;
        if (!(componentPath in loaders)) {
            const possibleValues = Object.keys(loaders).map((key) => key.replace('./', '').replace('.vue', ''));

            throw new Error(`Vue controller "${name}" does not exist. Possible values: ${possibleValues.join(', ')}`);
        }

        if (typeof vueControllers[componentPath] === 'undefined') {
            const module = loaders[componentPath]();
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

/**
 * Maps the keys returned by `import.meta.glob()` to the `./<name>.vue` form
 * produced by Webpack's `require.context()`, by stripping the directory prefix
 * shared by every key (e.g. `./vue/controllers/`).
 */
function normalizeGlobKeys(keys: string[]): Record<string, string> {
    if (keys.length === 0) {
        return {};
    }

    const segments = keys.map((key) => key.split('/'));

    // Number of leading directory segments shared by every key (the filename is never included).
    let commonLength = Math.min(...segments.map((parts) => parts.length - 1));
    for (let i = 0; i < commonLength; i++) {
        const segment = segments[0][i];
        if (!segments.every((parts) => parts[i] === segment)) {
            commonLength = i;
            break;
        }
    }

    const mapping: Record<string, string> = {};
    keys.forEach((key, index) => {
        mapping[key] = `./${segments[index].slice(commonLength).join('/')}`;
    });

    return mapping;
}
