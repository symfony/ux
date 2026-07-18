/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/// <reference types="@types/webpack-env" />

import type { ComponentClass, FunctionComponent } from 'react';

type Component = string | FunctionComponent<object> | ComponentClass<object, any>;

// A single entry from Vite's / Rsbuild's `import.meta.glob()`: an eagerly-imported
// module, or a function returning one for a lazy glob.
type GlobModule = { default: Component };
type GlobResult = Record<string, GlobModule | (() => Promise<GlobModule>)>;

declare global {
    function resolveReactComponent(name: string): Component;

    interface Window {
        resolveReactComponent(name: string): Component;
    }
}

export function registerReactControllerComponents(context: __WebpackModuleApi.RequireContext | GlobResult) {
    const reactControllers: { [key: string]: Component } = {};

    if (typeof context === 'function') {
        // Webpack's `require.context()`
        context.keys().forEach((key) => {
            reactControllers[key] = context(key).default;
        });
    } else {
        // Vite's / Rsbuild's `import.meta.glob(..., { eager: true })`
        const keyMapping = normalizeGlobKeys(Object.keys(context));
        Object.entries(context).forEach(([key, module]) => {
            if (typeof module === 'function') {
                throw new Error(
                    `React controller "${key}" could not be registered from a lazy "import.meta.glob()". Enable the "eager" option, e.g. import.meta.glob('./react/controllers/**/*.{jsx,tsx}', { eager: true }).`
                );
            }

            reactControllers[keyMapping[key]] = module.default;
        });
    }

    // Expose a global React loader to allow rendering from the Stimulus controller
    window.resolveReactComponent = (name: string): Component => {
        const component = reactControllers[`./${name}.jsx`] || reactControllers[`./${name}.tsx`];
        if (typeof component === 'undefined') {
            const possibleValues = Object.keys(reactControllers).map((key) =>
                key.replace('./', '').replace('.jsx', '').replace('.tsx', '')
            );

            if (possibleValues.includes(name)) {
                throw new Error(`
                    React controller "${name}" could not be resolved. Ensure the module exports the controller as a default export.`);
            }

            throw new Error(`React controller "${name}" does not exist. Possible values: ${possibleValues.join(', ')}`);
        }

        return component;
    };
}

/**
 * Maps the keys returned by `import.meta.glob()` to the `./<name>.<ext>` form
 * produced by Webpack's `require.context()`, by stripping the directory prefix
 * shared by every key (e.g. `./react/controllers/`).
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
