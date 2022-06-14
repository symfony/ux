/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

export function registerReactControllerComponents(context: __WebpackModuleApi.RequireContext) {
    const reactControllers: { [key: string]: object } = {};

    const importAllReactComponents = (r: __WebpackModuleApi.RequireContext) => {
        r.keys().forEach((key) => (reactControllers[key] = r(key).default));
    };

    importAllReactComponents(context);

    // Expose a global React loader to allow rendering from the Stimulus controller
    (window as any).resolveReactComponent = (name: string): object => {
        const component = reactControllers[`./${name}.jsx`] || reactControllers[`./${name}.tsx`];
        if (typeof component === 'undefined') {
            throw new Error('React controller "' + name + '" does not exist');
        }

        return component;
    };
}
