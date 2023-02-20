/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { ComponentClass, FunctionComponent } from 'react';

type Component = string | FunctionComponent<object> | ComponentClass<object, any>;

declare global {
    function resolveReactComponent(name: string): Component;

    interface Window {
        resolveReactComponent(name: string): Component;
    }
}

export function registerReactControllerComponents(context: __WebpackModuleApi.RequireContext) {
    const reactControllers: { [key: string]: Component } = {};

    const importAllReactComponents = (r: __WebpackModuleApi.RequireContext) => {
        r.keys().forEach((key) => (reactControllers[key] = r(key).default));
    };

    importAllReactComponents(context);

    // Expose a global React loader to allow rendering from the Stimulus controller
    window.resolveReactComponent = (name: string): Component => {
        const component = reactControllers[`./${name}.jsx`] || reactControllers[`./${name}.tsx`];
        if (typeof component === 'undefined') {
            const possibleValues = Object.keys(reactControllers).map((key) =>
                key.replace('./', '').replace('.jsx', '').replace('.tsx', '')
            );
            throw new Error(`React controller "${name}" does not exist. Possible values: ${possibleValues.join(', ')}`);
        }

        return component;
    };
}
